<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\LeaveBalance;
use Carbon\Carbon;

class LeaveBalanceService
{
    public function __construct(
        private readonly LeaveEligibilityService $eligibilityService,
    ) {}

    public function ensureYearBalance(Employee $employee, int $year): LeaveBalance
    {
        $employee->loadMissing('leaveProfile');

        $annualQuota = $this->eligibilityService->annualLeaveQuota($employee->leaveProfile);

        $balance = LeaveBalance::query()->firstOrCreate(
            [
                'employee_id' => (int) $employee->id,
                'year' => $year,
            ],
            [
                'annual_quota_days' => $annualQuota,
                'used_days' => 0,
                'remaining_days' => $annualQuota,
            ]
        );

        $balance->annual_quota_days = $annualQuota;
        $balance->remaining_days = max(0, (int) $balance->annual_quota_days - (int) $balance->used_days);
        $balance->save();

        return $balance;
    }

    public function ensureBalanceForDate(Employee $employee, Carbon $at): LeaveBalance
    {
        $cycle = $this->resolveCycleForDate($employee, $at);

        return $this->ensureYearBalance($employee, $cycle['cycle_year']);
    }

    public function remainingDays(Employee $employee, int $year): int
    {
        return (int) $this->ensureYearBalance($employee, $year)->remaining_days;
    }

    public function remainingDaysForDate(Employee $employee, Carbon $at): int
    {
        return (int) $this->ensureBalanceForDate($employee, $at)->remaining_days;
    }

    public function consumeDays(Employee $employee, int $year, int $days): LeaveBalance
    {
        if ($days <= 0) {
            throw new LeaveRequestException('invalid_approved_days', 'عدد الأيام المعتمدة غير صالح.');
        }

        $balance = $this->ensureYearBalance($employee, $year);

        if ((int) $balance->remaining_days < $days) {
            throw new LeaveRequestException('insufficient_leave_balance', 'رصيد الإجازات لا يكفي لاعتماد هذا الطلب.');
        }

        $balance->used_days = (int) $balance->used_days + $days;
        $balance->remaining_days = max(0, (int) $balance->annual_quota_days - (int) $balance->used_days);
        $balance->save();

        return $balance;
    }

    public function consumeDaysForDate(Employee $employee, Carbon $at, int $days): LeaveBalance
    {
        $cycle = $this->resolveCycleForDate($employee, $at);

        return $this->consumeDays($employee, $cycle['cycle_year'], $days);
    }

    /**
     * @return array{cycle_year:int, cycle_start:Carbon, cycle_end:Carbon}
     */
    public function resolveCycleForDate(Employee $employee, Carbon $at): array
    {
        $employee->loadMissing('leaveProfile');

        $employmentStart = $employee->leaveProfile?->employment_start_date;

        if ($employmentStart === null) {
            $cycleStart = $at->copy()->startOfYear();
            $cycleEnd = $cycleStart->copy()->addYear()->subDay()->endOfDay();

            return [
                'cycle_year' => (int) $cycleStart->year,
                'cycle_start' => $cycleStart,
                'cycle_end' => $cycleEnd,
            ];
        }

        $month = (int) $employmentStart->month;
        $day = min((int) $employmentStart->day, Carbon::create($at->year, $month, 1)->daysInMonth);
        $anniversaryThisYear = Carbon::create($at->year, $month, $day)->startOfDay();

        $cycleStart = $at->copy()->startOfDay()->lt($anniversaryThisYear)
            ? $anniversaryThisYear->copy()->subYear()
            : $anniversaryThisYear;

        $cycleEnd = $cycleStart->copy()->addYear()->subDay()->endOfDay();

        return [
            'cycle_year' => (int) $cycleStart->year,
            'cycle_start' => $cycleStart,
            'cycle_end' => $cycleEnd,
        ];
    }
}
