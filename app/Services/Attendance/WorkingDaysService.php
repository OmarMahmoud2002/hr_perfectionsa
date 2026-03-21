<?php

namespace App\Services\Attendance;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Services\Payroll\PayrollPeriod;
use Illuminate\Support\Collection;

/**
 * حساب أيام العمل المفترضة في فترة الراتب الشهرية (22 → 21)
 */
class WorkingDaysService
{
    /**
     * حساب أيام العمل المفترضة لشهر الراتب
     * فترة الشهر: من 22 من الشهر السابق إلى 21 من الشهر الحالي
     * (مطروحاً منها الجمعة والإجازات الرسمية)
     *
     * @param  int    $month          شهر الراتب (1-12)
     * @param  int    $year           سنة الراتب
     * @param  array  $publicHolidays مصفوفة تواريخ الإجازات الرسمية (Y-m-d strings)
     * @return Collection<Carbon>
     */
    public function getWorkingDays(int $month, int $year, array $publicHolidays = []): Collection
    {
        [$periodStart, $periodEnd] = PayrollPeriod::resolve($month, $year);

        $holidaySet = collect($publicHolidays)->map(fn($d) => Carbon::parse($d)->toDateString())->all();

        $workingDays = collect();

        $period = CarbonPeriod::create($periodStart, $periodEnd);
        foreach ($period as $day) {
            // تجاهل الجمعة (5)
            if ($day->dayOfWeek === 5) {
                continue;
            }

            // تجاهل الإجازات الرسمية
            if (in_array($day->toDateString(), $holidaySet, true)) {
                continue;
            }

            $workingDays->push($day->copy());
        }

        return $workingDays;
    }

    /**
     * عدد أيام العمل فقط
     */
    public function countWorkingDays(int $month, int $year, array $publicHolidays = []): int
    {
        return $this->getWorkingDays($month, $year, $publicHolidays)->count();
    }
}
