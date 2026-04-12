<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluator_can_access_employees_index_but_cannot_access_sensitive_employee_and_leave_approval_routes(): void
    {
        $evaluator = User::factory()->create([
            'role' => 'user',
            'must_change_password' => false,
        ]);

        $employee = Employee::factory()->create([
            'name' => 'Target Employee',
            'is_active' => true,
        ]);

        $this->actingAs($evaluator)->get(route('employees.index'))->assertOk();
        $this->actingAs($evaluator)->get(route('employees.show', $employee))->assertForbidden();
        $this->actingAs($evaluator)->get(route('leave.approvals.index'))->assertForbidden();
    }

    public function test_evaluator_can_see_company_wide_employees_in_index_cards(): void
    {
        $evaluator = User::factory()->create([
            'role' => 'user',
            'must_change_password' => false,
        ]);

        $employeeA = Employee::factory()->create([
            'name' => 'Evaluator Scope Employee A',
            'is_active' => true,
        ]);

        $employeeB = Employee::factory()->create([
            'name' => 'Evaluator Scope Employee B',
            'is_active' => true,
        ]);

        $this->actingAs($evaluator)
            ->get(route('employees.index', ['cards' => 1]))
            ->assertOk()
            ->assertSee($employeeA->name)
            ->assertSee($employeeB->name);
    }

    public function test_employee_cannot_access_leave_approvals_or_other_employee_details_directly(): void
    {
        $employee = Employee::factory()->create([
            'name' => 'Self Employee',
            'is_active' => true,
        ]);

        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => false,
        ]);

        $otherEmployee = Employee::factory()->create([
            'name' => 'Other Employee',
            'is_active' => true,
        ]);

        $this->actingAs($employeeUser)->get(route('leave.approvals.index'))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('employees.show', $otherEmployee))->assertForbidden();
    }

    public function test_department_manager_cannot_open_employee_settings_directly(): void
    {
        $managerEmployee = Employee::factory()->create([
            'name' => 'Manager Employee',
            'is_active' => true,
            'is_department_manager' => true,
        ]);

        $department = Department::query()->create([
            'name' => 'Managed Department',
            'code' => 'MD',
            'manager_employee_id' => $managerEmployee->id,
            'is_active' => true,
        ]);

        $managerEmployee->update([
            'department_id' => $department->id,
            'is_department_manager' => true,
        ]);

        $managerUser = User::factory()->create([
            'role' => 'department_manager',
            'employee_id' => $managerEmployee->id,
            'must_change_password' => false,
        ]);

        $this->actingAs($managerUser)
            ->get(route('leave.approvals.employee-settings'))
            ->assertForbidden();
    }

    public function test_admin_can_access_sensitive_employee_and_leave_approval_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $employee = Employee::factory()->create([
            'name' => 'Admin Visible Employee',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('employees.index'))->assertOk();
        $this->actingAs($admin)->get(route('employees.show', $employee))->assertOk();
        $this->actingAs($admin)->get(route('leave.approvals.index'))->assertOk();
    }
}
