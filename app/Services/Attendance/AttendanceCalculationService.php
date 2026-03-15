<?php

namespace App\Services\Attendance;

use App\DTOs\AttendanceRowDTO;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

/**
 * حساب التأخير والـ Overtime لكل يوم
 */
class AttendanceCalculationService
{
    /**
     * حساب حضور يوم واحد
     *
     * @param  AttendanceRowDTO  $row
     * @param  array             $settings        إعدادات الحساب
     * @param  array             $publicHolidays  مصفوفة تواريخ الإجازات (Y-m-d)
     * @return array{late_minutes: int, overtime_minutes: int, work_minutes: int, is_absent: bool, notes: string|null}
     */
    public function calculateDay(
        AttendanceRowDTO $row,
        array $settings,
        array $publicHolidays = []
    ): array {
        $workStart = $settings['work_start_time']    ?? '09:00';
        $workEnd   = $settings['work_end_time']      ?? '17:00';
        $otStart   = $settings['overtime_start_time'] ?? '17:30';

        // ============================================
        // الخطوة 0: تحقق هل هو إجازة رسمية
        // ============================================
        if (in_array($row->date->toDateString(), $publicHolidays, true)) {
            return [
                'late_minutes'     => 0,
                'overtime_minutes' => 0,
                'work_minutes'     => 0,
                'is_absent'        => false,
                'notes'            => 'إجازة رسمية',
            ];
        }

        // ============================================
        // الخطوة 1: تحقق هل هو يوم إجازة أسبوعي (جمعة)
        // ============================================
        $dayOfWeek = $row->date->dayOfWeek;

        // الجمعة (5) لا تظهر في الملف، لكن إن ظهرت نتجاهلها
        if ($dayOfWeek === 5) {
            return [
                'late_minutes'     => 0,
                'overtime_minutes' => 0,
                'work_minutes'     => 0,
                'is_absent'        => false,
                'notes'            => 'إجازة أسبوعية (جمعة)',
            ];
        }

        // ============================================
        // الخطوة 2: تحقق من الغياب
        // ============================================
        if ($row->isAbsent || ($row->clockIn === null && $row->clockOut === null)) {
            return [
                'late_minutes'     => 0,
                'overtime_minutes' => 0,
                'work_minutes'     => 0,
                'is_absent'        => true,
                'notes'            => $row->notes,
            ];
        }

        // ============================================
        // الخطوة 3: تطبيق القيم الافتراضية
        // ============================================
        $notes    = $row->notes ?? '';
        $clockIn  = $row->clockIn;
        $clockOut = $row->clockOut;

        if ($clockIn === null) {
            $clockIn = $this->parseTime($workStart, $row->date);
            $notes   = trim($notes . ' | حضور بدون بصمة (افتراضي ' . $workStart . ')');
        }

        if ($clockOut === null) {
            $clockOut = $this->parseTime($workEnd, $row->date);
            $notes    = trim($notes . ' | انصراف بدون بصمة (افتراضي ' . $workEnd . ')');
        }

        // ============================================
        // الخطوة 4: حساب التأخير (مع فترة السماح)
        // ============================================
        $workStartTime = $this->parseTime($workStart, $row->date);
        $graceMinutes  = (int) ($settings['late_grace_minutes'] ?? 30);
        $lateThreshold = $workStartTime->copy()->addMinutes($graceMinutes);
        $lateMinutes   = 0;

        // التأخير لا يُحتسب إلا إذا تجاوز الموظف فترة السماح
        // مثال: العمل 9:00، السماح 30 دقيقة → الحضور حتى 9:30 لا يُعدّ تأخيراً
        // لكن لو حضر 9:40 → يُحتسب 40 دقيقة تأخير (من 9:00 وليس من 9:30)
        if ($clockIn->gt($lateThreshold)) {
            $lateMinutes = (int) $clockIn->diffInMinutes($workStartTime);
        }

        // ============================================
        // الخطوة 5: حساب Overtime
        // ============================================
        $otStartTime     = $this->parseTime($otStart, $row->date);
        $overtimeMinutes = 0;

        if ($clockOut->gt($otStartTime)) {
            $overtimeMinutes = (int) $clockOut->diffInMinutes($otStartTime);
        }

        // ============================================
        // الخطوة 6: حساب ساعات العمل الفعلية
        // ============================================
        $workMinutes = max(0, (int) $clockIn->diffInMinutes($clockOut));

        return [
            'late_minutes'     => $lateMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'work_minutes'     => $workMinutes,
            'is_absent'        => false,
            'notes'            => $notes ?: null,
        ];
    }

    /**
     * مساعد: تحليل نص الوقت بالنسبة لتاريخ معين
     */
    private function parseTime(string $timeString, Carbon $date): Carbon
    {
        [$hours, $minutes] = explode(':', $timeString);
        return $date->copy()->setTime((int) $hours, (int) $minutes, 0);
    }
}
