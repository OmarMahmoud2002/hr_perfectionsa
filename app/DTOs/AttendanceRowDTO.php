<?php

namespace App\DTOs;

use Carbon\Carbon;

/**
 * DTO يمثل صف واحد من ملف Excel
 */
class AttendanceRowDTO
{
    public function __construct(
        public readonly string   $acNo,
        public readonly string   $name,
        public readonly Carbon   $date,
        public readonly ?Carbon  $clockIn,
        public readonly ?Carbon  $clockOut,
        public readonly bool     $isAbsent,
        public readonly ?string  $notes = null,
    ) {}

    /**
     * هل الموظف حضر في هذا اليوم
     */
    public function isPresent(): bool
    {
        return !$this->isAbsent && ($this->clockIn !== null || $this->clockOut !== null);
    }

    /**
     * هل يوجد clock_in فقط
     */
    public function hasClockInOnly(): bool
    {
        return $this->clockIn !== null && $this->clockOut === null;
    }

    /**
     * هل يوجد clock_out فقط
     */
    public function hasClockOutOnly(): bool
    {
        return $this->clockIn === null && $this->clockOut !== null;
    }
}
