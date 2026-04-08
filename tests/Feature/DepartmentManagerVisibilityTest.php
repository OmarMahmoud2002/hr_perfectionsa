<?php

namespace Tests\Feature;

use App\Models\DailyPerformanceEntry;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagerVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_manager_can_only_view_his_department_employees(): void
    {
        [$managerUser, $managerEmployee, $departmentA] = $this->createDepartmentManager('Dept Manager A', 'DM-A', 'Dept A', 'DA');

        [, $employeeInA] = $this->createEmployeeUser('Employee A1', 'A-1', $departmentA->id);

        $departmentB = Department::query()->create([
            'name' => 'Dept B',
            'code' => 'DB',
            'is_active' => true,
        ]);

        [, $employeeInB] = $this->createEmployeeUser('Employee B1', 'B-1', $departmentB->id);

        $this->actingAs($managerUser)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee($employeeInA->name)
            ->assertDontSee($employeeInB->name);

        $this->actingAs($managerUser)
            ->get(route('employees.show', $employeeInA))
            ->assertOk();

        $this->actingAs($managerUser)
            ->get(route('employees.show', $employeeInB))
            ->assertStatus(403);

        $this->assertNotNull($managerEmployee);
    }

    public function test_department_manager_daily_performance_review_is_scoped_and_blocks_other_department_upsert(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 10, 0, 0, config('app.timezone')));

        [$managerUser, , $departmentA] = $this->createDepartmentManager('Dept Manager Review', 'DM-R', 'Review A', 'RA');

        [, $employeeInA] = $this->createEmployeeUser('Review Employee A', 'RA-1', $departmentA->id);

        $departmentB = Department::query()->create([
            'name' => 'Review B',
            'code' => 'RB',
            'is_active' => true,
        ]);

        [, $employeeInB] = $this->createEmployeeUser('Review Employee B', 'RB-1', $departmentB->id);

        $entryA = DailyPerformanceEntry::query()->create([
            'employee_id' => $employeeInA->id,
            'work_date' => '2026-04-10',
            'project_name' => 'Dept A Task',
            'work_description' => 'Entry A',
            'submitted_at' => now(),
        ]);

        $entryB = DailyPerformanceEntry::query()->create([
            'employee_id' => $employeeInB->id,
            'work_date' => '2026-04-10',
            'project_name' => 'Dept B Task',
            'work_description' => 'Entry B',
            'submitted_at' => now(),
        ]);

        $this->actingAs($managerUser)
            ->get(route('daily-performance.review.index', ['date' => '2026-04-10']))
            ->assertOk()
            ->assertSee($employeeInA->name)
            ->assertDontSee($employeeInB->name);

        $this->actingAs($managerUser)
            ->post(route('daily-performance.review.upsert', $entryA), [
                'rating' => 4,
                'comment' => 'Good work',
            ])
            ->assertRedirect();

        $this->actingAs($managerUser)
            ->post(route('daily-performance.review.upsert', $entryB), [
                'rating' => 4,
                'comment' => 'Should fail',
            ])
            ->assertStatus(403);
    }

    public function test_department_manager_vote_scope_is_limited_to_his_department(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 10, 0, 0, config('app.timezone')));

        [$managerUser, , $departmentA] = $this->createDepartmentManager('Vote Manager', 'DM-V', 'Vote A', 'VA');

        [, $candidateInA] = $this->createEmployeeUser('Candidate A', 'CA-1', $departmentA->id);

        $departmentB = Department::query()->create([
            'name' => 'Vote B',
            'code' => 'VB',
            'is_active' => true,
        ]);

        [, $candidateInB] = $this->createEmployeeUser('Candidate B', 'CB-1', $departmentB->id);

        $this->actingAs($managerUser)
            ->get(route('employee-of-month.vote.page'))
            ->assertOk()
            ->assertSee($candidateInA->name)
            ->assertDontSee($candidateInB->name);

        $this->actingAs($managerUser)
            ->postJson(route('employee-of-month.vote.store'), [
                'voted_employee_id' => $candidateInB->id,
            ])
            ->assertStatus(422)
            ->assertJson([
                'reason' => 'candidate_outside_department',
            ]);

        $this->actingAs($managerUser)
            ->postJson(route('employee-of-month.vote.store'), [
                'voted_employee_id' => $candidateInA->id,
            ])
            ->assertCreated()
            ->assertJson([
                'status' => 'created',
                'voted_employee_id' => $candidateInA->id,
            ]);
    }

    private function createDepartmentManager(string $name, string $acNo, string $departmentName, string $departmentCode): array
    {
        $managerEmployee = Employee::factory()->create([
            'name' => $name,
            'ac_no' => $acNo,
            'is_active' => true,
            'is_department_manager' => true,
        ]);

        $department = Department::query()->create([
            'name' => $departmentName,
            'code' => $departmentCode,
            'manager_employee_id' => $managerEmployee->id,
            'is_active' => true,
        ]);

        $managerEmployee->update([
            'department_id' => $department->id,
            'is_department_manager' => true,
        ]);

        $managerUser = User::factory()->create([
            'name' => $name,
            'role' => 'department_manager',
            'employee_id' => $managerEmployee->id,
            'must_change_password' => false,
        ]);

        return [$managerUser, $managerEmployee, $department];
    }

    private function createEmployeeUser(string $name, string $acNo, ?int $departmentId = null): array
    {
        $employee = Employee::factory()->create([
            'name' => $name,
            'ac_no' => $acNo,
            'department_id' => $departmentId,
            'is_active' => true,
            'is_department_manager' => false,
        ]);

        $user = User::factory()->create([
            'name' => $name,
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => false,
        ]);

        return [$user, $employee];
    }
}
