<?php

namespace App\Services\Excel;

use Carbon\Carbon;

/**
 * مساعد لتحويل أوقات Excel المختلفة إلى Carbon
 */
class ExcelTimeHelper
{
    /**
     * تحويل قيمة الوقت من Excel إلى Carbon
     * يدعم: رقم عشري (0.375=09:00)، نص "09:40"، نص "9:40"، نص "09:40:00"
     */
    public static function parseTime(mixed $value, ?Carbon $referenceDate = null): ?Carbon
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $date = $referenceDate ?? Carbon::today();

        // إذا كان رقماً عشرياً (Excel Time Serial)
        if (is_float($value) || (is_string($value) && is_numeric($value) && str_contains($value, '.'))) {
            $numericValue = (float) $value;
            // Excel يخزن الوقت كنسبة من 24 ساعة
            $totalSeconds = (int) round($numericValue * 86400);
            $hours   = (int) floor($totalSeconds / 3600);
            $minutes = (int) floor(($totalSeconds % 3600) / 60);

            if ($hours >= 24) {
                return null; // قيمة غير صالحة
            }

            return $date->copy()->setTime($hours, $minutes, 0);
        }

        // إذا كان integer (مثل 9 تعني 9 ساعات)
        if (is_int($value)) {
            if ($value >= 0 && $value < 24) {
                return $date->copy()->setTime($value, 0, 0);
            }
            return null;
        }

        // نص
        $value = trim((string) $value);

        if (empty($value)) {
            return null;
        }

        // تجريب تنسيقات مختلفة
        $formats = [
            'H:i:s',   // 09:40:00
            'H:i',     // 09:40
            'G:i:s',   // 9:40:00
            'G:i',     // 9:40
            'h:i A',   // 09:40 AM
            'h:i:s A', // 09:40:00 AM
            'H:i:s A', // 09:40:00 AM
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed) {
                    return $date->copy()->setTime($parsed->hour, $parsed->minute, 0);
                }
            } catch (\Exception) {
                // المتابعة للتنسيق التالي
            }
        }

        // محاولة أخيرة بـ Carbon::parse
        try {
            $parsed = Carbon::parse($value);
            return $date->copy()->setTime($parsed->hour, $parsed->minute, 0);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * تحويل قيمة التاريخ من Excel إلى Carbon
     */
    public static function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        // رقم عشري Excel (منذ 1899-12-30)
        if (is_float($value) || is_int($value)) {
            try {
                // Excel date serial: عدد الأيام منذ 1 يناير 1900
                $excelDate = (int) $value;
                // تصحيح الخطأ المشهور في Excel (يعتقد أن 1900 سنة كبيسة)
                if ($excelDate > 59) {
                    $excelDate--;
                }
                return Carbon::createFromTimestamp(($excelDate - 25569) * 86400);
            } catch (\Exception) {
                return null;
            }
        }

        $value = trim((string) $value);

        if (empty($value)) {
            return null;
        }

        $formats = [
            'Y-m-d',
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
            'Y/m/d',
            'd.m.Y',
            'n/j/Y',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed && $parsed->year > 2000) {
                    return $parsed->startOfDay();
                }
            } catch (\Exception) {
                // المتابعة
            }
        }

        try {
            $parsed = Carbon::parse($value);
            if ($parsed->year > 2000) {
                return $parsed->startOfDay();
            }
        } catch (\Exception) {
            return null;
        }

        return null;
    }
}
