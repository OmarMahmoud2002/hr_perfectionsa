<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegressionAccessSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_manager_still_have_core_access(): void
    {
        foreach (['admin', 'hr', 'manager'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'must_change_password' => false,
            ]);

            $this->actingAs($user)->get(route('dashboard'))->assertOk();
            $this->actingAs($user)->get(route('employees.index'))->assertOk();
            $this->actingAs($user)->get(route('attendance.index'))->assertOk();
            $this->actingAs($user)->get(route('payroll.index'))->assertOk();
            $this->actingAs($user)->get(route('import.form'))->assertOk();
            $this->actingAs($user)->get(route('settings.index'))->assertOk();
            $this->actingAs($user)->get(route('employee-of-month.admin.index'))->assertOk();
            $this->actingAs($user)->get(route('leave.approvals.index'))->assertOk();
        }
    }

    public function test_employee_flows_still_work_and_admin_pages_are_forbidden(): void
    {
        $employee = Employee::factory()->create([
            'name' => 'Smoke Employee',
            'ac_no' => 'SM-E1',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Smoke Employee',
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => false,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('tasks.my.index'))->assertOk();
        $this->actingAs($user)->get(route('daily-performance.employee.index'))->assertOk();
        $this->actingAs($user)->get(route('attendance.remote.page'))->assertOk();
        $this->actingAs($user)->get(route('employee-of-month.vote.page'))->assertOk();
        $this->actingAs($user)->get(route('leave.requests.index'))->assertOk();

        $this->actingAs($user)->get(route('payroll.index'))->assertStatus(403);
        $this->actingAs($user)->get(route('import.form'))->assertStatus(403);
        $this->actingAs($user)->get(route('settings.index'))->assertStatus(403);
        $this->actingAs($user)->get(route('employee-of-month.admin.index'))->assertStatus(403);
    }
}
