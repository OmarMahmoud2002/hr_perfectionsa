<?php

namespace App\Services\Payroll;

use Carbon\Carbon;

class PayrollPeriod
{
    /**
     * جلب فترة شهر الراتب [start, end].
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function resolve(int $month, int $year): array
    {
        $start = Carbon::createMidnightDate($year, $month, 1)
            ->subMonth()
            ->day(22)
            ->startOfDay();

        $end = Carbon::createMidnightDate($year, $month, 21)->endOfDay();

        return [$start, $end];
    }

    /**
     * بداية فترة شهر الراتب: 22 من الشهر السابق.
     */
    public static function startDate(int $month, int $year): Carbon
    {
        return self::resolve($month, $year)[0];
    }

    /**
     * نهاية فترة شهر الراتب: 21 من الشهر الحالي.
     */
    public static function endDate(int $month, int $year): Carbon
    {
        return self::resolve($month, $year)[1];
    }

    /**
     * تحويل تاريخ يومي إلى (month/year) لشهر الراتب المقابل.
     */
    public static function monthForDate(Carbon $date): array
    {
        $payrollMonthDate = Carbon::createMidnightDate($date->year, $date->month, 1);

        if ($date->day >= 22) {
            $payrollMonthDate->addMonth();
        }

        return [
            'month' => $payrollMonthDate->month,
            'year'  => $payrollMonthDate->year,
        ];
    }
}
