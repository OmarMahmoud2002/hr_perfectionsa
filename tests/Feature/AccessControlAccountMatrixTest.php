<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlAccountMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_access_matrix_for_core_sensitive_routes(): void
    {
        $department = Department::query()->create([
            'name' => 'Matrix Department',
            'code' => 'MX',
            'is_active' => true,
        ]);

        $targetEmployee = Employee::factory()->create([
            'name' => 'Matrix Target Employee',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $managerEmployee = Employee::factory()->create([
            'name' => 'Matrix Department Manager',
            'department_id' => $department->id,
            'is_active' => true,
            'is_department_manager' => true,
        ]);

        $department->update(['manager_employee_id' => $managerEmployee->id]);

        $employeeUserEmployee = Employee::factory()->create([
            'name' => 'Matrix Employee User',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $officeGirlEmployee = Employee::factory()->create([
            'name' => 'Matrix Office Girl',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $users = [
            'admin' => User::factory()->create([
                'role' => 'admin',
                'must_change_password' => false,
            ]),
            'manager' => User::factory()->create([
                'role' => 'manager',
                'must_change_password' => false,
            ]),
            'hr' => User::factory()->create([
                'role' => 'hr',
                'must_change_password' => false,
            ]),
            'department_manager' => User::factory()->create([
                'role' => 'department_manager',
                'employee_id' => $managerEmployee->id,
                'must_change_password' => false,
            ]),
            'employee' => User::factory()->create([
                'role' => 'employee',
                'employee_id' => $employeeUserEmployee->id,
                'must_change_password' => false,
            ]),
            'office_girl' => User::factory()->create([
                'role' => 'office_girl',
                'employee_id' => $officeGirlEmployee->id,
                'must_change_password' => false,
            ]),
            'user' => User::factory()->create([
                'role' => 'user',
                'must_change_password' => false,
            ]),
        ];

        $matrix = [
            'admin' => ['employees_index' => 200, 'employees_show' => 200, 'leave_approvals' => 200, 'leave_employee_settings' => 200],
            'manager' => ['employees_index' => 200, 'employees_show' => 200, 'leave_approvals' => 200, 'leave_employee_settings' => 403],
            'hr' => ['employees_index' => 200, 'employees_show' => 200, 'leave_approvals' => 200, 'leave_employee_settings' => 200],
            'department_manager' => ['employees_index' => 200, 'employees_show' => 200, 'leave_approvals' => 200, 'leave_employee_settings' => 403],
            'employee' => ['employees_index' => 200, 'employees_show' => 403, 'leave_approvals' => 403, 'leave_employee_settings' => 403],
            'office_girl' => ['employees_index' => 200, 'employees_show' => 403, 'leave_approvals' => 403, 'leave_employee_settings' => 403],
            'user' => ['employees_index' => 200, 'employees_show' => 403, 'leave_approvals' => 403, 'leave_employee_settings' => 403],
        ];

        foreach ($users as $role => $account) {
            $expected = $matrix[$role];

            $this->actingAs($account)->get(route('employees.index'))->assertStatus($expected['employees_index']);
            $this->actingAs($account)->get(route('employees.show', $targetEmployee))->assertStatus($expected['employees_show']);
            $this->actingAs($account)->get(route('leave.approvals.index'))->assertStatus($expected['leave_approvals']);
            $this->actingAs($account)->get(route('leave.approvals.employee-settings'))->assertStatus($expected['leave_employee_settings']);
        }
    }
}
