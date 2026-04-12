<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLeaveProfile;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\Leave\LeaveRequestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveUiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_open_leave_page_and_submit_request(): void
    {
        $employee = Employee::factory()->create([
            'name' => 'Employee Leave User',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => false,
        ]);

        EmployeeLeaveProfile::query()->create([
            'employee_id' => $employee->id,
            'employment_start_date' => now()->subDays(200)->toDateString(),
            'required_work_days_before_leave' => 90,
            'annual_leave_quota' => 14,
        ]);

        $this->actingAs($user)
            ->get(route('leave.requests.index'))
            ->assertOk()
            ->assertSee('طلب إجازة جديد');

        $start = now()->addDays(3)->toDateString();
        $end = now()->addDays(5)->toDateString();

        $this->actingAs($user)
            ->post(route('leave.requests.store'), [
                'start_date' => $start,
                'end_date' => $end,
                'reason' => 'Vacation',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'start_date' => $start,
            'end_date' => $end,
            'requested_days' => 3,
            'status' => 'pending',
        ]);
    }

    public function test_department_manager_approvals_page_is_scoped_to_his_requests(): void
    {
        $managerEmployee = Employee::factory()->create(['name' => 'Main Manager']);
        $managerUser = User::factory()->create([
            'role' => 'department_manager',
            'employee_id' => $managerEmployee->id,
            'must_change_password' => false,
        ]);

        $otherManagerEmployee = Employee::factory()->create(['name' => 'Other Manager']);

        $departmentA = Department::query()->create([
            'name' => 'Dept A',
            'code' => 'DA',
            'manager_employee_id' => $managerEmployee->id,
            'is_active' => true,
        ]);

        $departmentB = Department::query()->create([
            'name' => 'Dept B',
            'code' => 'DB',
            'manager_employee_id' => $otherManagerEmployee->id,
            'is_active' => true,
        ]);

        $empA = Employee::factory()->create(['name' => 'Scoped Employee', 'department_id' => $departmentA->id]);
        $empB = Employee::factory()->create(['name' => 'Hidden Employee', 'department_id' => $departmentB->id]);

        LeaveRequest::query()->create([
            'employee_id' => $empA->id,
            'department_id' => $departmentA->id,
            'manager_employee_id' => $managerEmployee->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'requested_days' => 2,
            'status' => 'pending',
            'hr_status' => 'pending',
            'manager_status' => 'pending',
            'submitted_at' => now(),
        ]);

        LeaveRequest::query()->create([
            'employee_id' => $empB->id,
            'department_id' => $departmentB->id,
            'manager_employee_id' => $otherManagerEmployee->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'requested_days' => 2,
            'status' => 'pending',
            'hr_status' => 'pending',
            'manager_status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($managerUser)
            ->get(route('leave.approvals.index', [
                'month' => (int) now()->month,
                'year' => (int) now()->year,
            ]))
            ->assertOk()
            ->assertSee('Scoped Employee')
            ->assertDontSee('Hidden Employee');
    }

    public function test_hr_can_fully_approve_hr_only_request_from_ui_endpoint(): void
    {
        $employee = Employee::factory()->create(['name' => 'Leave Target']);
        $hr = User::factory()->create([
            'role' => 'hr',
            'must_change_password' => false,
        ]);

        $leaveRequest = LeaveRequest::query()->create([
            'employee_id' => $employee->id,
            'department_id' => null,
            'manager_employee_id' => null,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'requested_days' => 4,
            'status' => 'pending',
            'hr_status' => 'pending',
            'manager_status' => 'not_required',
            'submitted_at' => now(),
        ]);

        $this->actingAs($hr)
            ->post(route('leave.approvals.decide', $leaveRequest), [
                'decision' => 'approved',
                'note' => 'Approved',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'approved',
            'hr_status' => 'approved',
            'final_approved_days' => 4,
        ]);
    }

    public function test_manager_can_approve_hr_request_only(): void
    {
        $managerUser = User::factory()->create([
            'role' => 'manager',
            'must_change_password' => false,
        ]);

        $hrEmployee = Employee::factory()->create(['name' => 'HR Employee']);
        $hrUser = User::factory()->create([
            'role' => 'hr',
            'employee_id' => $hrEmployee->id,
            'must_change_password' => false,
        ]);

        EmployeeLeaveProfile::query()->create([
            'employee_id' => $hrEmployee->id,
            'employment_start_date' => now()->subDays(300)->toDateString(),
            'required_work_days_before_leave' => 90,
            'annual_leave_quota' => 15,
        ]);

        $leaveRequest = app(LeaveRequestService::class)->submit(
            $hrEmployee,
            Carbon::create((int) now()->year, 8, 10),
            Carbon::create((int) now()->year, 8, 12),
            'HR leave request',
            now()
        );

        $this->actingAs($managerUser)
            ->post(route('leave.approvals.decide', $leaveRequest), [
                'decision' => 'approved',
                'note' => 'Approved by manager',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'approved',
            'hr_status' => 'not_required',
            'manager_status' => 'approved',
            'final_approved_days' => 3,
        ]);
    }

    public function test_manager_cannot_approve_non_hr_request(): void
    {
        $managerUser = User::factory()->create([
            'role' => 'manager',
            'must_change_password' => false,
        ]);

        $employee = Employee::factory()->create(['name' => 'Regular Employee']);
        User::factory()->create([
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => false,
        ]);

        $leaveRequest = LeaveRequest::query()->create([
            'employee_id' => $employee->id,
            'department_id' => null,
            'manager_employee_id' => null,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
            'requested_days' => 2,
            'status' => 'pending',
            'hr_status' => 'pending',
            'manager_status' => 'not_required',
            'submitted_at' => now(),
        ]);

        $this->actingAs($managerUser)
            ->post(route('leave.approvals.decide', $leaveRequest), [
                'decision' => 'approved',
            ])
            ->assertForbidden();
    }
}
