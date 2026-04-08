<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\EmployeeLeaveProfile;
use App\Services\Setting\SettingService;
use Carbon\Carbon;

class LeaveEligibilityService
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    public function evaluate(Employee $employee, ?Carbon $asOf = null): array
    {
        $asOf ??= now();
        $employee->loadMissing('leaveProfile');

        $profile = $employee->leaveProfile;

        $requiredDays = $this->requiredWorkDays($profile);
        $annualQuota = $this->annualLeaveQuota($profile);
        $employmentStart = $profile?->employment_start_date;

        if ($employmentStart === null) {
            return [
                'eligible' => false,
                'reason' => 'employment_start_date_missing',
                'service_days' => 0,
                'required_work_days' => $requiredDays,
                'days_remaining_to_eligibility' => $requiredDays,
                'annual_leave_quota' => $annualQuota,
            ];
        }

        $serviceDays = max(0, $employmentStart->startOfDay()->diffInDays($asOf->copy()->startOfDay()) + 1);
        $remainingToEligibility = max(0, $requiredDays - $serviceDays);

        return [
            'eligible' => $remainingToEligibility === 0,
            'reason' => $remainingToEligibility === 0 ? 'ok' : 'minimum_service_not_reached',
            'service_days' => $serviceDays,
            'required_work_days' => $requiredDays,
            'days_remaining_to_eligibility' => $remainingToEligibility,
            'annual_leave_quota' => $annualQuota,
        ];
    }

    public function annualLeaveQuota(?EmployeeLeaveProfile $profile = null): int
    {
        return max(0, (int) ($profile?->annual_leave_quota
            ?? $this->settingService->get('default_annual_leave_days', 21)));
    }

    public function requiredWorkDays(?EmployeeLeaveProfile $profile = null): int
    {
        return max(0, (int) ($profile?->required_work_days_before_leave
            ?? $this->settingService->get('default_required_work_days_before_leave', 120)));
    }
}
