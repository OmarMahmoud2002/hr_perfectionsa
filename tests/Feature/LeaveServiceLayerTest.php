<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLeaveProfile;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\Leave\LeaveApprovalService;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Leave\LeaveEligibilityService;
use App\Services\Leave\LeaveRequestException;
use App\Services\Leave\LeaveRequestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveServiceLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_submit_leave_when_minimum_service_days_not_reached(): void
    {
        $employee = Employee::factory()->create([
            'is_active' => true,
        ]);

        EmployeeLeaveProfile::query()->create([
            'employee_id' => $employee->id,
            'employment_start_date' => now()->subDays(10)->toDateString(),
            'required_work_days_before_leave' => 120,
            'annual_leave_quota' => 21,
        ]);

        $eligibility = app(LeaveEligibilityService::class)->evaluate($employee, now());

        $this->assertFalse($eligibility['eligible']);
        $this->assertSame('minimum_service_not_reached', $eligibility['reason']);
        $this->assertGreaterThan(0, $eligibility['days_remaining_to_eligibility']);
    }

    public function test_leave_request_uses_hr_only_flow_when_department_manager_not_required(): void
    {
        $employee = Employee::factory()->create([
            'is_active' => true,
        ]);

        EmployeeLeaveProfile::query()->create([
            'employee_id' => $employee->id,
            'employment_start_date' => now()->subDays(200)->toDateString(),
            'required_work_days_before_leave' => 120,
            'annual_leave_quota' => 12,
        ]);

        $request = app(LeaveRequestService::class)->submit(
            $employee,
            Carbon::create((int) now()->year, 5, 10),
            Carbon::create((int) now()->year, 5, 12),
            'Personal leave',
            now()
        );

        $this->assertSame('pending', $request->status);
        $this->assertSame('pending', $request->hr_status);
        $this->assertSame('not_required', $request->manager_status);
        $this->assertSame(3, $request->requested_days);
    }

    public function test_leave_request_dual_approval_with_hr_partial_approval_updates_balance(): void
    {
        $managerEmployee = Employee::factory()->create([
            'is_active' => true,
        ]);

        $managerUser = User::factory()->create([
            'role' => 'department_manager',
            'employee_id' => $managerEmployee->id,
            'must_change_password' => false,
        ]);

        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'manager_employee_id' => $managerEmployee->id,
            'is_active' => true,
        ]);

        $managerEmployee->update([
            'department_id' => $department->id,
            'is_department_manager' => true,
        ]);

        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        EmployeeLeaveProfile::query()->create([
            'employee_id' => $employee->id,
            'employment_start_date' => now()->subDays(200)->toDateString(),
            'required_work_days_before_leave' => 90,
            'annual_leave_quota' => 10,
        ]);

        $request = app(LeaveRequestService::class)->submit(
            $employee,
            Carbon::create((int) now()->year, 6, 1),
            Carbon::create((int) now()->year, 6, 4),
            'Annual leave',
            now()
        );

        $approvalService = app(LeaveApprovalService::class);

        $approvalService->decide($request, $managerUser, 'approved', null, 'Approved by manager');

        $hrUser = User::factory()->create([
            'role' => 'hr',
            'must_change_password' => false,
        ]);

        $updated = $approvalService->decide($request, $hrUser, 'partially_approved', 2, 'Reduced by HR');

        $this->assertSame('partially_approved', $updated->status);
        $this->assertSame('approved', $updated->manager_status);
        $this->assertSame('partially_approved', $updated->hr_status);
        $this->assertSame(2, $updated->final_approved_days);
        $this->assertNotNull($updated->finalized_at);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'year' => app(LeaveBalanceService::class)->resolveCycleForDate($employee, $request->start_date->copy())['cycle_year'],
            'annual_quota_days' => 10,
            'used_days' => 2,
            'remaining_days' => 8,
        ]);

        $this->assertDatabaseCount('leave_request_approvals', 2);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $request->id,
            'status' => 'partially_approved',
            'final_approved_days' => 2,
        ]);

        $this->assertDatabaseHas('leave_request_approvals', [
            'leave_request_id' => $request->id,
            'actor_user_id' => $managerUser->id,
            'actor_role' => 'department_manager',
            'decision' => 'approved',
        ]);
    }

    public function test_anniversary_cycle_year_is_used_before_and_after_employment_anniversary(): void
    {
        $employee = Employee::factory()->create(['is_active' => true]);

        EmployeeLeaveProfile::query()->create([
            'employee_id' => $employee->id,
            'employment_start_date' => '2024-06-15',
            'required_work_days_before_leave' => 120,
            'annual_leave_quota' => 21,
        ]);

        $balanceService = app(LeaveBalanceService::class);

        $beforeAnniversary = Carbon::create(2026, 4, 10);
        $beforeCycle = $balanceService->resolveCycleForDate($employee, $beforeAnniversary);
        $this->assertSame(2025, $beforeCycle['cycle_year']);

        $afterAnniversary = Carbon::create(2026, 8, 1);
        $afterCycle = $balanceService->resolveCycleForDate($employee, $afterAnniversary);
        $this->assertSame(2026, $afterCycle['cycle_year']);
    }

    public function test_leave_request_cannot_span_two_different_anniversary_cycles(): void
    {
        $employee = Employee::factory()->create(['is_active' => true]);

        EmployeeLeaveProfile::query()->create([
            'employee_id' => $employee->id,
            'employment_start_date' => '2024-06-15',
            'required_work_days_before_leave' => 120,
            'annual_leave_quota' => 21,
        ]);

        $this->expectException(LeaveRequestException::class);

        app(LeaveRequestService::class)->submit(
            $employee,
            Carbon::create(2026, 6, 14),
            Carbon::create(2026, 6, 16),
            'Boundary spanning request',
            Carbon::create(2026, 6, 1)
        );
    }
}
