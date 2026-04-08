<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveRequestService
{
    public function __construct(
        private readonly LeaveEligibilityService $eligibilityService,
        private readonly LeaveBalanceService $balanceService,
    ) {}

    public function submit(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate,
        ?string $reason = null,
        ?Carbon $submittedAt = null
    ): LeaveRequest {
        $submittedAt ??= now();

        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();

        if ($end->lt($start)) {
            throw new LeaveRequestException('invalid_date_range', 'تاريخ نهاية الإجازة يجب أن يكون بعد تاريخ البداية أو مساويًا له.');
        }

        $requestedDays = $start->diffInDays($end) + 1;

        if ($requestedDays <= 0) {
            throw new LeaveRequestException('invalid_requested_days', 'عدد أيام الإجازة غير صالح.');
        }

        $eligibility = $this->eligibilityService->evaluate($employee, $submittedAt);

        if (! ($eligibility['eligible'] ?? false)) {
            throw new LeaveRequestException((string) ($eligibility['reason'] ?? 'ineligible_for_leave'), 'لا يمكنك تقديم طلب إجازة الآن.');
        }

        $startCycle = $this->balanceService->resolveCycleForDate($employee, $start);
        $endCycle = $this->balanceService->resolveCycleForDate($employee, $end);

        if ((int) $startCycle['cycle_year'] !== (int) $endCycle['cycle_year']) {
            throw new LeaveRequestException(
                'cross_cycle_leave_request_not_allowed',
                'لا يمكن تقديم طلب يغطي دورتين سنويتين مختلفتين. يرجى تقسيمه إلى طلبين.'
            );
        }

        $remaining = $this->balanceService->remainingDays($employee, (int) $startCycle['cycle_year']);

        if ($requestedDays > $remaining) {
            throw new LeaveRequestException('insufficient_leave_balance', 'عدد الأيام المطلوبة أكبر من الرصيد المتبقي.');
        }

        $employee->loadMissing('department.managerEmployee');

        $managerEmployeeId = $employee->department?->manager_employee_id;

        // If the manager of the department is the same employee, HR-only flow is used.
        if ($managerEmployeeId !== null && (int) $managerEmployeeId === (int) $employee->id) {
            $managerEmployeeId = null;
        }

        return DB::transaction(function () use ($employee, $start, $end, $requestedDays, $reason, $submittedAt, $managerEmployeeId) {
            return LeaveRequest::query()->create([
                'employee_id' => (int) $employee->id,
                'department_id' => $employee->department_id,
                'manager_employee_id' => $managerEmployeeId,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'requested_days' => $requestedDays,
                'reason' => $reason,
                'status' => 'pending',
                'hr_status' => 'pending',
                'manager_status' => $managerEmployeeId === null ? 'not_required' : 'pending',
                'submitted_at' => $submittedAt,
            ]);
        });
    }
}
