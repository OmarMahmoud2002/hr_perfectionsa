<?php

namespace App\Services\Attendance;

use App\Models\ImportBatch;
use App\Models\PublicHoliday;
use Illuminate\Support\Collection;

/**
 * إدارة الإجازات الرسمية المرتبطة بدفعة استيراد
 */
class PublicHolidayService
{
    /**
     * جلب تواريخ الإجازات الرسمية لدفعة معينة كـ array من strings
     *
     * @param  ImportBatch  $batch
     * @return array  ['2026-03-01', '2026-03-02', ...]
     */
    public function getHolidayDates(ImportBatch $batch): array
    {
        return $batch->publicHolidays()
            ->pluck('date')
            ->map(fn ($d) => is_string($d) ? $d : $d->toDateString())
            ->all();
    }

    /**
     * إضافة إجازة رسمية لدفعة معينة
     */
    public function addHoliday(ImportBatch $batch, string $date, string $name): PublicHoliday
    {
        return $batch->publicHolidays()->create([
            'date' => $date,
            'name' => $name,
        ]);
    }

    /**
     * حذف إجازة رسمية
     */
    public function removeHoliday(PublicHoliday $holiday): void
    {
        $holiday->delete();
    }

    /**
     * حذف جميع الإجازات المرتبطة بدفعة
     */
    public function removeAllForBatch(ImportBatch $batch): void
    {
        $batch->publicHolidays()->delete();
    }
}
