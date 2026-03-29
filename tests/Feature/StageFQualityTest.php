<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeMonthTask;
use App\Models\EmployeeMonthTaskAssignment;
use App\Models\User;
use App\Services\EmployeeOfMonth\EmployeeOfMonthScoringService;
use App\Services\Payroll\PayrollPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StageFQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_matrix_for_task_related_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $evaluator = User::factory()->create([
            'role' => 'user',
            'must_change_password' => false,
        ]);

        [$employeeUser] = $this->createEmployeeUser('Employee Matrix', 'AC-F-EMP-1');

        $this->actingAs($admin)->get(route('tasks.admin.index'))->assertOk();
        $this->actingAs($admin)->get(route('employee-of-month.admin.index'))->assertOk();
        $this->actingAs($admin)->get(route('tasks.evaluator.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('tasks.my.index'))->assertForbidden();

        $this->actingAs($evaluator)->get(route('tasks.evaluator.index'))->assertOk();
        $this->actingAs($evaluator)->get(route('employees.index'))->assertOk();
        $this->actingAs($evaluator)->get(route('account.my'))->assertOk();
        $this->actingAs($evaluator)->get(route('tasks.admin.index'))->assertForbidden();
        $this->actingAs($evaluator)->get(route('employee-of-month.admin.index'))->assertForbidden();
        $this->actingAs($evaluator)->get(route('tasks.my.index'))->assertForbidden();

        $this->actingAs($employeeUser)->get(route('tasks.my.index'))->assertOk();
        $this->actingAs($employeeUser)->get(route('tasks.evaluator.index'))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('tasks.admin.index'))->assertForbidden();
    }

    public function test_evaluator_page_does_not_expose_assigned_employee_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $evaluator = User::factory()->create([
            'role' => 'user',
            'must_change_password' => false,
        ]);

        $employee = Employee::factory()->create([
            'name' => 'Sensitive Employee Name',
            'ac_no' => 'AC-PRIV-1',
            'is_active' => true,
        ]);

        $month = (int) now()->month;
        $year = (int) now()->year;
        [$start, $end] = PayrollPeriod::resolve($month, $year);

        $task = EmployeeMonthTask::query()->create([
            'title' => 'Privacy Task',
            'description' => 'Task shown to evaluator only by title/description',
            'period_month' => $month,
            'period_year' => $year,
            'period_start_date' => $start->toDateString(),
            'period_end_date' => $end->toDateString(),
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        EmployeeMonthTaskAssignment::query()->create([
            'task_id' => $task->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($evaluator)->get(route('tasks.evaluator.index', [
            'month' => $month,
            'year' => $year,
        ]));

        $response->assertOk();
        $response->assertSee('Privacy Task');
        $response->assertDontSee('Sensitive Employee Name');
    }

    public function test_scoring_weighted_points_and_task_tie_breaker(): void
    {
        $service = app(EmployeeOfMonthScoringService::class);

        $metricsData = [
            'total_valid_votes' => 2,
            'rows' => collect([
                [
                    'employee_id' => 1,
                    'employee' => null,
                    'votes_count' => 1,
                    'work_minutes' => 480,
                    'late_minutes' => 10,
                    'overtime_minutes' => 0,
                    'admin_score' => null,
                    'task_score_raw' => 8.0,
                    'assigned_tasks_count' => 2,
                    'evaluated_tasks_count' => 2,
                ],
                [
                    'employee_id' => 2,
                    'employee' => null,
                    'votes_count' => 1,
                    'work_minutes' => 480,
                    'late_minutes' => 10,
                    'overtime_minutes' => 0,
                    'admin_score' => null,
                    'task_score_raw' => 6.0,
                    'assigned_tasks_count' => 2,
                    'evaluated_tasks_count' => 2,
                ],
                [
                    'employee_id' => 3,
                    'employee' => null,
                    'votes_count' => 0,
                    'work_minutes' => 0,
                    'late_minutes' => 10,
                    'overtime_minutes' => 0,
                    'admin_score' => null,
                    'task_score_raw' => null,
                    'assigned_tasks_count' => 2,
                    'evaluated_tasks_count' => 0,
                ],
            ]),
        ];

        $result = $service->calculateForMonth((int) now()->month, (int) now()->year, $metricsData);

        /** @var Collection<int, array<string, mixed>> $scoredRows */
        $scoredRows = $result['scored_rows'];
        $this->assertCount(3, $scoredRows);
        $this->assertSame('v3_weighted_points', $result['formula_version']);

        foreach ($scoredRows as $row) {
            $this->assertGreaterThanOrEqual(0.0, (float) $row['final_score']);
            $this->assertLessThanOrEqual(100.0, (float) $row['final_score']);

            $this->assertGreaterThanOrEqual(0.0, (float) $row['breakdown']['task_points']);
            $this->assertGreaterThanOrEqual(0.0, (float) $row['breakdown']['vote_points']);
            $this->assertGreaterThanOrEqual(0.0, (float) $row['breakdown']['work_hours_points']);
            $this->assertGreaterThanOrEqual(0.0, (float) $row['breakdown']['punctuality_points']);
        }

        $employeeOne = $scoredRows->firstWhere('employee_id', 1);
        $employeeTwo = $scoredRows->firstWhere('employee_id', 2);
        $employeeThree = $scoredRows->firstWhere('employee_id', 3);

        // All late minutes are equal and non-zero, so current formula gives 0 punctuality points للجميع.
        $this->assertSame(0.0, (float) $employeeOne['breakdown']['punctuality_points']);
        $this->assertSame(0.0, (float) $employeeTwo['breakdown']['punctuality_points']);
        $this->assertSame(0.0, (float) $employeeThree['breakdown']['punctuality_points']);

        // Missing task evaluation must start from zero (no neutral fallback).
        $this->assertSame(0.0, (float) $employeeThree['breakdown']['task_points']);

        // Employee 1 and 2 tie on vote/work/late; tasks decide ranking.
        $this->assertSame(1, (int) $scoredRows->first()['employee_id']);
        $this->assertGreaterThan(
            (float) $employeeTwo['breakdown']['task_points'],
            (float) $employeeOne['breakdown']['task_points']
        );
    }

    private function createEmployeeUser(string $name, string $acNo): array
    {
        $employee = Employee::factory()->create([
            'name' => $name,
            'ac_no' => $acNo,
            'is_active' => true,
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
