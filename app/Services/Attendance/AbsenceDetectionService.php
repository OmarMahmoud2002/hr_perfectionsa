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
     * @param  Employee  $employee
     * @param  int       $month
     * @param  int       $year
     * @param  array     $publicHolidays  تواريخ الإجازات الرسمية (Y-m-d)
     * @return array{
     *   total_working_days: int,
     *   total_present_days: int,
     *   total_absent_days: int,
     *   total_late_minutes: int,
     *   total_overtime_minutes: int,
     *   records: Collection
     * }
     */
    public function getMonthlyStats(Employee $employee, int $month, int $year, array $publicHolidays = []): array
    {
        // أيام العمل المفترضة في فترة الراتب (22 شهر سابق → 21 شهر حالي)
        $workingDays = $this->workingDaysService->getWorkingDays(
            $month,
            $year,
            $publicHolidays
        );

        // فترة الراتب
        $periodStart = Carbon::create($year, $month, 22)->subMonthNoOverflow()->toDateString();
        $periodEnd   = Carbon::create($year, $month, 21)->toDateString();

        // سجلات الحضور الفعلية من قاعدة البيانات
        $records = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->get()
            ->keyBy(fn ($r) => Carbon::parse($r->date)->toDateString());

        // --- الخطوة الأولى: تحديد الأسابيع التي غاب فيها الموظف طوال الأسبوع ---
        // (موظف مثلاً رحل يوم 10، فكل الأيام من 11→21 غياب كامل في أسابيعها)
        // هذه الأسابيع لا تستحق إجازة أسبوعية لأن الموظف لم يحضر أصلاً
        $weekWorkingDaysCount = [];
        $weekAbsentDaysCount  = [];

        foreach ($workingDays as $day) {
            $dateStr          = $day->toDateString();
            $record           = $records->get($dateStr);
            $dow              = $day->dayOfWeek;
            $daysFromSaturday = ($dow + 1) % 7;
            $weekKey          = $day->copy()->subDays($daysFromSaturday)->toDateString();

            $weekWorkingDaysCount[$weekKey] = ($weekWorkingDaysCount[$weekKey] ?? 0) + 1;

            if ($record === null || $record->is_absent) {
                $weekAbsentDaysCount[$weekKey] = ($weekAbsentDaysCount[$weekKey] ?? 0) + 1;
            }
        }

        // أسبوع "غياب كامل" = كل أيام العمل فيه غائب (لا يستحق إجازة أسبوعية)
        $fullyAbsentWeeks = [];
        foreach ($weekWorkingDaysCount as $weekKey => $total) {
            if (($weekAbsentDaysCount[$weekKey] ?? 0) === $total) {
                $fullyAbsentWeeks[$weekKey] = true;
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

            if ($record === null || $record->is_absent) {
                $dow              = $day->dayOfWeek;
                $daysFromSaturday = ($dow + 1) % 7;
                $weekKey          = $day->copy()->subDays($daysFromSaturday)->toDateString();

                // أسبوع غياب كامل: كل الأيام تُحسب غياباً حقيقياً بدون إجازة أسبوعية
                if (isset($fullyAbsentWeeks[$weekKey])) {
                    $absentDays++;
                    continue;
                }

                if (!isset($weekAbsenceCounts[$weekKey])) {
                    $weekAbsenceCounts[$weekKey] = 0;
                }
                $weekAbsenceCounts[$weekKey]++;

                if ($weekAbsenceCounts[$weekKey] === 1) {
                    // أول غياب في الأسبوع = إجازة أسبوعية، لا يُخصم
                    $weeklyLeaveDays++;
                } else {
                    $absentDays++;
                }
            } else {
                $presentDays++;
            }
        }

        return [
            'total_working_days'      => $workingDays->count(),
            'total_present_days'      => $presentDays,
            'total_weekly_leave_days' => $weeklyLeaveDays,
            'total_absent_days'       => $absentDays,
            'total_late_minutes'      => (int) $records->where('is_absent', false)->sum('late_minutes'),
            'total_overtime_minutes'  => (int) $records->sum('overtime_minutes'),
            'records'                 => $records,
        ];
    }

    /**
     * حساب ملخص الحضور الشهري لجميع الموظفين في دفعة معينة
     *
     * @param  Collection<Employee>  $employees
     * @param  int                   $month
     * @param  int                   $year
     * @param  array                 $publicHolidays
     * @return Collection  مجموعة من مصفوفات stats لكل موظف
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
     * يطبّق قاعدة الإجازة الأسبوعية: الجمعة + يوم غياب واحد في الأسبوع = إجازة (غير مخصوم)
     *
     * @return Collection  مصفوفة بتفاصيل كل يوم (date, date_str, day_name, status, record)
     */
    public function getDailyBreakdown(Employee $employee, int $month, int $year, array $publicHolidays = []): Collection
    {
        // فترة الراتب: 22 شهر سابق → 21 شهر حالي
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

            // مفتاح الأسبوع: السبت هو بداية الأسبوع (0=أحد..5=جمعة..6=سبت)
            $daysFromSaturday = ($dayOfWeek + 1) % 7; // سبت=0, أحد=1, ..., جمعة=6
            $weekKey          = $current->copy()->subDays($daysFromSaturday)->toDateString();

            // تحديد نوع اليوم
            if ($dayOfWeek === 5) {
                $status = 'friday';
            } elseif (isset($holidaySet[$dateStr])) {
                $status = 'public_holiday';
            } else {
                $record = $records->get($dateStr);
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
                'record'    => $records->get($dateStr),
                'week_key'  => $weekKey,
            ]);

            $current->addDay();
        }

        // --- الخطوة الثانية: تحديد الأسابيع التي غاب فيها الموظف طوال الأسبوع ---
        // أسبوع "غياب كامل" = كل أيام العمل فيه (غير الجمعة وغير الإجازات الرسمية) غائب
        // هذه الأسابيع لا تستحق إجازة أسبوعية
        $weekWorkingCount = [];
        $weekAbsentCount  = [];

        foreach ($rawDays as $day) {
            if (in_array($day['status'], ['friday', 'public_holiday'])) {
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
        // كل موظف له يومان عطلة في الأسبوع: الجمعة + يوم آخر بأي يوم
        // أول غياب غير الجمعة في كل أسبوع → إجازة أسبوعية (لا يُخصم)
        // الغياب الثاني فأكثر في نفس الأسبوع → يُحتسب غياباً
        // استثناء: أسبوع الغياب الكامل لا يستحق إجازة أسبوعية
        $weekAbsenceCounts = [];

        return $rawDays->map(function ($day) use (&$weekAbsenceCounts, $fullyAbsentWeeks) {
            if ($day['status'] === 'absent') {
                $weekKey = $day['week_key'];

                // أسبوع غياب كامل: يبقى 'absent' بدون إجازة أسبوعية
                if (!isset($fullyAbsentWeeks[$weekKey])) {
                    if (!isset($weekAbsenceCounts[$weekKey])) {
                        $weekAbsenceCounts[$weekKey] = 0;
                    }
                    $weekAbsenceCounts[$weekKey]++;

                    if ($weekAbsenceCounts[$weekKey] === 1) {
                        // أول غياب في الأسبوع = إجازة أسبوعية
                        $day['status'] = 'weekly_leave';
                    }
                    // الثاني فأكثر يبقى 'absent'
                }
            }

            unset($day['week_key']);
            return $day;
        });
    }
}
