<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

/**
 * DTO يمثل مجموعة سجلات حضور موظف واحد لكل الشهر
 */
class EmployeeAttendanceDTO
{
    /**
     * @param Collection<AttendanceRowDTO> $records
     */
    public function __construct(
        public readonly string     $acNo,
        public readonly string     $name,
        public readonly Collection $records,
    ) {}

    /**
     * إجمالي أيام الحضور
     */
    public function getPresentDaysCount(): int
    {
        return $this->records->filter(fn ($r) => $r->isPresent())->count();
    }

    /**
     * إجمالي أيام الغياب
     */
    public function getAbsentDaysCount(): int
    {
        return $this->records->filter(fn ($r) => $r->isAbsent)->count();
    }
}
