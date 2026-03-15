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

        $presentDays      = 0;
        $absentDays       = 0;
        $weeklyLeaveDays  = 0;
        $weekAbsenceCounts = [];

        foreach ($workingDays as $day) {
            $dateStr = $day->toDateString();
            $record  = $records->get($dateStr);

            if ($record === null || $record->is_absent) {
                // حساب مفتاح الأسبوع (السبت بداية الأسبوع)
                $dow              = $day->dayOfWeek; // 0=أحد, 1=اثنين, ..., 5=جمعة, 6=سبت
                $daysFromSaturday = ($dow + 1) % 7;  // سبت=0, أحد=1, ..., جمعة=6
                $weekKey          = $day->copy()->subDays($daysFromSaturday)->toDateString();

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

        // --- الخطوة الثانية: تطبيق قاعدة "الإجازة الأسبوعية" ---
        // كل موظف له يومان عطلة في الأسبوع: الجمعة + يوم آخر بأي يوم
        // أول غياب غير الجمعة في كل أسبوع → إجازة أسبوعية (لا يُخصم)
        // الغياب الثاني فأكثر في نفس الأسبوع → يُحتسب غياباً
        $weekAbsenceCounts = [];

        return $rawDays->map(function ($day) use (&$weekAbsenceCounts) {
            if ($day['status'] === 'absent') {
                $weekKey = $day['week_key'];
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

            unset($day['week_key']);
            return $day;
        });
    }
}
