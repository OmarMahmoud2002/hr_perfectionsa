<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * كشف أيام الغياب لكل موظف في شهر معين
 * يقارن أيام العمل المفترضة بسجلات الحضور الفعلية
 */
class AbsenceDetectionService
{
    public function __construct(
        private readonly WorkingDaysService $workingDaysService
    ) {}

    /**
     * حساب ملخص الحضور الشهري لموظف واحد
     *
     * قاعدة manual_status:
     *   - 'absent'        → غياب حقيقي دائماً (يتجاوز خوارزمية الإجازة الأسبوعية)
     *   - 'present'       → حضور دائماً (حتى لو is_absent=true في البيانات الأصلية)
     *   - 'weekly_leave'  → إجازة أسبوعية دائماً
     *   - 'public_holiday'→ يُضاف لقائمة الإجازات الرسمية ويُستبعد من أيام العمل
     *   - null            → يُحسب تلقائياً من البيانات الأصلية (is_absent, clock_in…)
     */
    public function getMonthlyStats(Employee $employee, int $month, int $year, array $publicHolidays = []): array
    {
        // فترة الراتب
        $periodStart = Carbon::create($year, $month, 22)->subMonthNoOverflow()->toDateString();
        $periodEnd   = Carbon::create($year, $month, 21)->toDateString();

        // سجلات الحضور الفعلية من قاعدة البيانات
        $records = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->get()
            ->keyBy(fn ($r) => Carbon::parse($r->date)->toDateString());

        // إضافة أيام الإجازات الرسمية المعيّنة يدوياً إلى قائمة الإجازات
        // حتى تُستبعد من workingDays في WorkingDaysService
        $manualPublicHolidayDates = $records
            ->where('manual_status', 'public_holiday')
            ->keys()
            ->toArray();

        $effectivePublicHolidays = array_unique(array_merge($publicHolidays, $manualPublicHolidayDates));

        // أيام العمل المفترضة في فترة الراتب (22 شهر سابق → 21 شهر حالي)
        $workingDays = $this->workingDaysService->getWorkingDays(
            $month,
            $year,
            $effectivePublicHolidays
        );

        // --- الخطوة الأولى: تحديد الأسابيع التي غاب فيها الموظف طوال الأسبوع ---
        // manual_status يُؤخذ بعين الاعتبار لتحديد هل اليوم غياب أم لا
        $weekWorkingDaysCount = [];
        $weekAbsentDaysCount  = [];   // غياب حقيقي فقط (لحساب fullyAbsentWeeks وخصم أيام الغياب)
        $weekNonPresentCount  = [];   // غياب + إجازة أسبوعية (لحساب بونص الحضور الكامل)

        foreach ($workingDays as $day) {
            $dateStr          = $day->toDateString();
            $record           = $records->get($dateStr);
            $dow              = $day->dayOfWeek;
            $daysFromSaturday = ($dow + 1) % 7;
            $weekKey          = $day->copy()->subDays($daysFromSaturday)->toDateString();

            $weekWorkingDaysCount[$weekKey] = ($weekWorkingDaysCount[$weekKey] ?? 0) + 1;

            // غياب حقيقي (manual absent | auto absent)
            $isAbsent = $this->resolveIsAbsent($record);

            // غير حاضر: يشمل الغياب الحقيقي + الإجازة الأسبوعية (يدوية أو تلقائية)
            // الإجازة الأسبوعية اليدوية: manual_status='weekly_leave'
            // الإجازة الأسبوعية التلقائية: record=null أو is_absent=true (سيُحتسب عبر resolveIsAbsent)
            $isNonPresent = $isAbsent || $record?->manual_status === 'weekly_leave';

            if ($isAbsent) {
                $weekAbsentDaysCount[$weekKey] = ($weekAbsentDaysCount[$weekKey] ?? 0) + 1;
            }
            if ($isNonPresent) {
                $weekNonPresentCount[$weekKey] = ($weekNonPresentCount[$weekKey] ?? 0) + 1;
            }
        }

        // أسبوع "غياب كامل" = كل أيام العمل فيه غياب حقيقي (لا يستحق إجازة أسبوعية)
        $fullyAbsentWeeks    = [];
        // أسبوع "حضور كامل" = لا يوجد أي يوم غير حاضر (لا غياب ولا إجازة أسبوعية)
        $fullAttendanceWeeks = 0;

        foreach ($weekWorkingDaysCount as $weekKey => $total) {
            if (($weekAbsentDaysCount[$weekKey] ?? 0) === $total) {
                // كل أيام الأسبوع غياب حقيقي → أسبوع غياب كامل
                $fullyAbsentWeeks[$weekKey] = true;
            } elseif (($weekNonPresentCount[$weekKey] ?? 0) === 0) {
                // لا يوجد أي يوم غير حاضر → أهلٌ لبونص الحضور الكامل
                $weekSaturday  = Carbon::parse($weekKey);
                $weekThursday  = $weekSaturday->copy()->addDays(5);
                $isFullWeek    = $weekSaturday->toDateString() >= $periodStart
                              && $weekThursday->toDateString() <= $periodEnd;

                if ($isFullWeek) {
                    $fullAttendanceWeeks++;
                }
            }
        }

        // --- الخطوة الثانية: حساب الحضور والغياب مع قاعدة الإجازة الأسبوعية ---
        $presentDays       = 0;
        $absentDays        = 0;
        $weeklyLeaveDays   = 0;
        $weekAbsenceCounts = [];

        foreach ($workingDays as $day) {
            $dateStr = $day->toDateString();
            $record  = $records->get($dateStr);
            $ms      = $record?->manual_status;

            // ① إجازة أسبوعية يدوية → تُحسب مباشرةً
            if ($ms === 'weekly_leave') {
                $weeklyLeaveDays++;
                continue;
            }

            // ② غياب يدوي → غياب حقيقي دائماً (يتجاوز خوارزمية الإجازة الأسبوعية التلقائية)
            if ($ms === 'absent') {
                $absentDays++;
                continue;
            }

            // ③ تحديد الغياب للأيام غير اليدوية أو اليدوية بـ 'present'
            $isAbsent = $this->resolveIsAbsent($record);

            if ($isAbsent) {
                $dow              = $day->dayOfWeek;
                $daysFromSaturday = ($dow + 1) % 7;
                $weekKey          = $day->copy()->subDays($daysFromSaturday)->toDateString();

                if (isset($fullyAbsentWeeks[$weekKey])) {
                    $absentDays++;
                    continue;
                }

                if (!isset($weekAbsenceCounts[$weekKey])) {
                    $weekAbsenceCounts[$weekKey] = 0;
                }
                $weekAbsenceCounts[$weekKey]++;

                if ($weekAbsenceCounts[$weekKey] === 1) {
                    $weeklyLeaveDays++;
                } else {
                    $absentDays++;
                }
            } else {
                $presentDays++;
            }
        }

        // --- حساب إجمالي التأخير والأوفرتايم مع مراعاة manual_status ---
        // الأيام ذات manual_status غير 'present' (أو null مع is_absent=true) لا تُضاف
        $totalLateMinutes = 0;
        $totalOtMinutes   = 0;

        foreach ($records as $record) {
            $ms = $record->manual_status;

            // حالات لا تستحق late/ot: غياب أو إجازة أسبوعية أو إجازة رسمية (يدوية)
            if (in_array($ms, ['absent', 'weekly_leave', 'public_holiday'], true)) {
                continue;
            }

            // بيانات أصلية: غياب (is_absent=true) ولا يوجد تجاوز يدوي بـ 'present'
            if ($ms === null && $record->is_absent) {
                continue;
            }

            $totalLateMinutes += $record->late_minutes;
            $totalOtMinutes   += $record->overtime_minutes;
        }

        return [
            'total_working_days'          => $workingDays->count(),
            'total_present_days'          => $presentDays,
            'total_weekly_leave_days'     => $weeklyLeaveDays,
            'total_absent_days'           => $absentDays,
            'total_full_attendance_weeks' => $fullAttendanceWeeks,
            'total_late_minutes'          => $totalLateMinutes,
            'total_overtime_minutes'      => $totalOtMinutes,
            'records'                     => $records,
        ];
    }

    /**
     * هل اليوم غياب؟ يأخذ manual_status بعين الاعتبار.
     * - 'absent'       → true  (غياب دائماً)
     * - 'present'      → false (حضور دائماً حتى لو is_absent=true)
     * - 'weekly_leave' → false (ليس غياباً لأغراض اكتشاف أسبوع الغياب الكامل)
     * - null           → يرجع is_absent من السجل الأصلي (أو true إذا لا سجل)
     */
    private function resolveIsAbsent(?AttendanceRecord $record): bool
    {
        if ($record === null) {
            return true;
        }

        return match ($record->manual_status) {
            'absent'                    => true,
            'present', 'weekly_leave', 'public_holiday' => false,
            default                     => (bool) $record->is_absent,
        };
    }

    /**
     * حساب ملخص الحضور الشهري لجميع الموظفين في دفعة معينة
     */
    public function getBulkMonthlyStats(Collection $employees, int $month, int $year, array $publicHolidays = []): Collection
    {
        return $employees->map(function (Employee $employee) use ($month, $year, $publicHolidays) {
            $stats = $this->getMonthlyStats($employee, $month, $year, $publicHolidays);
            return array_merge(['employee' => $employee], $stats);
        });
    }

    /**
     * جلب كل سجلات الحضور في شهر بشكل مُعبّأ مع حالة كل يوم
     * يطبّق قاعدة الإجازة الأسبوعية ويحترم manual_status
     */
    public function getDailyBreakdown(Employee $employee, int $month, int $year, array $publicHolidays = []): Collection
    {
        $firstDay = Carbon::create($year, $month, 22)->subMonthNoOverflow();
        $lastDay  = Carbon::create($year, $month, 21);

        $records = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('date', [$firstDay->toDateString(), $lastDay->toDateString()])
            ->get()
            ->keyBy(fn ($r) => Carbon::parse($r->date)->toDateString());

        $holidaySet = collect($publicHolidays)->map(fn ($d) => Carbon::parse($d)->toDateString())->flip();

        // --- الخطوة الأولى: بناء الأيام بحالاتها الأساسية ---
        $rawDays = collect();
        $current = $firstDay->copy();

        while ($current->lte($lastDay)) {
            $dateStr   = $current->toDateString();
            $dayOfWeek = $current->dayOfWeek;

            $daysFromSaturday = ($dayOfWeek + 1) % 7;
            $weekKey          = $current->copy()->subDays($daysFromSaturday)->toDateString();

            $record   = $records->get($dateStr);
            $isManual = $record && $record->manual_status;

            // manual_status يأخذ الأولوية الكاملة على أي منطق تلقائي
            if ($isManual) {
                $status = $record->manual_status;
            } elseif ($dayOfWeek === 5) {
                $status = 'friday';
            } elseif (isset($holidaySet[$dateStr])) {
                $status = 'public_holiday';
            } else {
                if ($record === null || $record->is_absent) {
                    $status = 'absent';
                } elseif ($record->late_minutes > 0) {
                    $status = 'late';
                } else {
                    $status = 'present';
                }
            }

            $rawDays->push([
                'date'      => $current->copy(),
                'date_str'  => $dateStr,
                'day_name'  => $current->locale('ar')->dayName,
                'status'    => $status,
                'record'    => $record,
                'week_key'  => $weekKey,
                'is_manual' => (bool) $isManual,
            ]);

            $current->addDay();
        }

        // --- الخطوة الثانية: تحديد الأسابيع التي غاب فيها الموظف طوال الأسبوع ---
        $weekWorkingCount = [];
        $weekAbsentCount  = [];

        foreach ($rawDays as $day) {
            if (in_array($day['status'], ['friday', 'public_holiday', 'weekly_leave'])) {
                continue;
            }
            $weekKey = $day['week_key'];
            $weekWorkingCount[$weekKey] = ($weekWorkingCount[$weekKey] ?? 0) + 1;
            if ($day['status'] === 'absent') {
                $weekAbsentCount[$weekKey] = ($weekAbsentCount[$weekKey] ?? 0) + 1;
            }
        }

        $fullyAbsentWeeks = [];
        foreach ($weekWorkingCount as $weekKey => $total) {
            if (($weekAbsentCount[$weekKey] ?? 0) === $total) {
                $fullyAbsentWeeks[$weekKey] = true;
            }
        }

        // --- الخطوة الثالثة: تطبيق قاعدة "الإجازة الأسبوعية" ---
        // الأيام اليدوية: حالتها نهائية لا تُغيَّر
        $weekAbsenceCounts = [];

        return $rawDays->map(function ($day) use (&$weekAbsenceCounts, $fullyAbsentWeeks) {
            // يوم يدوي: لا يخضع لأي تحويل تلقائي
            if ($day['is_manual']) {
                unset($day['week_key'], $day['is_manual']);
                return $day;
            }

            if ($day['status'] === 'absent') {
                $weekKey = $day['week_key'];

                if (!isset($fullyAbsentWeeks[$weekKey])) {
                    if (!isset($weekAbsenceCounts[$weekKey])) {
                        $weekAbsenceCounts[$weekKey] = 0;
                    }
                    $weekAbsenceCounts[$weekKey]++;

                    if ($weekAbsenceCounts[$weekKey] === 1) {
                        $day['status'] = 'weekly_leave';
                    }
                }
            }

            unset($day['week_key'], $day['is_manual']);
            return $day;
        });
    }
}
