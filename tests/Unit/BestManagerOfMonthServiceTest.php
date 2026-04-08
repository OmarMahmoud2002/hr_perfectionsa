<?php

namespace Tests\Unit;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeOfMonthResult;
use App\Models\User;
use App\Services\EmployeeOfMonth\BestManagerOfMonthService;
use App\Services\EmployeeOfMonth\EmployeeOfMonthScoringService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BestManagerOfMonthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_of_month_constants_follow_top4_and_min90_rules(): void
    {
        $this->assertSame(4, EmployeeOfMonthScoringService::WINNERS_COUNT);
        $this->assertSame(90.0, (float) EmployeeOfMonthScoringService::MIN_RANKING_SCORE);
    }

    public function test_best_manager_selection_prefers_department_with_three_of_top_four_and_tie_breaks_by_late_minutes(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        [$managerA, $departmentA] = $this->createDepartmentManager('Manager A', 'M-A', 'Dept A', 'DA');
        [$managerB, $departmentB] = $this->createDepartmentManager('Manager B', 'M-B', 'Dept B', 'DB');

        $a1 = $this->createEmployeeOnly('A Winner 1', 'A-W1', $departmentA->id);
        $a2 = $this->createEmployeeOnly('A Winner 2', 'A-W2', $departmentA->id);
        $a3 = $this->createEmployeeOnly('A Winner 3', 'A-W3', $departmentA->id);
        $b1 = $this->createEmployeeOnly('B Winner 1', 'B-W1', $departmentB->id);

        $month = 3;
        $year = 2026;

        EmployeeOfMonthResult::query()->create([
            'employee_id' => $a1->id,
            'month' => $month,
            'year' => $year,
            'final_score' => 97,
            'breakdown' => ['task_points' => 35],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);

        EmployeeOfMonthResult::query()->create([
            'employee_id' => $a2->id,
            'month' => $month,
            'year' => $year,
            'final_score' => 96,
            'breakdown' => ['task_points' => 34],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);

        EmployeeOfMonthResult::query()->create([
            'employee_id' => $b1->id,
            'month' => $month,
            'year' => $year,
            'final_score' => 95,
            'breakdown' => ['task_points' => 33],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);

        EmployeeOfMonthResult::query()->create([
            'employee_id' => $a3->id,
            'month' => $month,
            'year' => $year,
            'final_score' => 94,
            'breakdown' => ['task_points' => 32],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);

        foreach ([$a1, $a2, $a3] as $employee) {
            AttendanceRecord::query()->create([
                'employee_id' => $employee->id,
                'date' => '2026-03-10',
                'clock_in' => '09:00:00',
                'clock_out' => '17:00:00',
                'is_absent' => false,
                'late_minutes' => 5,
                'overtime_minutes' => 0,
                'work_minutes' => 480,
            ]);
        }

        AttendanceRecord::query()->create([
            'employee_id' => $b1->id,
            'date' => '2026-03-10',
            'clock_in' => '09:30:00',
            'clock_out' => '17:00:00',
            'is_absent' => false,
            'late_minutes' => 30,
            'overtime_minutes' => 0,
            'work_minutes' => 450,
        ]);

        $winner = app(BestManagerOfMonthService::class)->resolveForMonth($month, $year);

        $this->assertNotNull($winner);
        $this->assertSame($departmentA->id, $winner['department']->id);
        $this->assertSame($managerA->id, $winner['manager']->id);

        $this->assertNotNull($managerB);
    }

    private function createDepartmentManager(string $name, string $acNo, string $departmentName, string $departmentCode): array
    {
        $managerEmployee = Employee::factory()->create([
            'name' => $name,
            'ac_no' => $acNo,
            'is_active' => true,
            'is_department_manager' => true,
        ]);

        User::factory()->create([
            'name' => $name,
            'role' => 'department_manager',
            'employee_id' => $managerEmployee->id,
            'must_change_password' => false,
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

        return [$managerEmployee, $department];
    }

    private function createEmployeeOnly(string $name, string $acNo, int $departmentId): Employee
    {
        $employee = Employee::factory()->create([
            'name' => $name,
            'ac_no' => $acNo,
            'department_id' => $departmentId,
            'is_active' => true,
            'is_department_manager' => false,
        ]);

        User::factory()->create([
            'name' => $name,
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => false,
        ]);

        return $employee;
    }
}
