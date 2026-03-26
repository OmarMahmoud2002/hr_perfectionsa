<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeMonthTask;
use App\Models\EmployeeMonthTaskAssignment;
use App\Models\EmployeeMonthTaskEvaluation;
use App\Models\User;
use App\Services\EmployeeOfMonth\EmployeeTaskScoreService;
use App\Services\EmployeeOfMonth\TaskEvaluationService;
use App\Services\Payroll\PayrollPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TaskServicesStageCTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluator_can_upsert_own_task_evaluation_and_other_evaluator_is_blocked(): void
    {
        $evaluatorOne = User::factory()->create([
            'role' => 'user',
            'must_change_password' => false,
        ]);

        $evaluatorTwo = User::factory()->create([
            'role' => 'user',
            'must_change_password' => false,
        ]);

        $creator = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $employee = Employee::factory()->create([
            'is_active' => true,
        ]);

        $month = (int) now()->month;
        $year = (int) now()->year;
        [$start, $end] = PayrollPeriod::resolve($month, $year);

        $task = EmployeeMonthTask::query()->create([
            'title' => 'Task A',
            'description' => 'Evaluate this task',
            'period_month' => $month,
            'period_year' => $year,
            'period_start_date' => $start->toDateString(),
            'period_end_date' => $end->toDateString(),
            'created_by' => $creator->id,
            'is_active' => true,
        ]);

        EmployeeMonthTaskAssignment::query()->create([
            'task_id' => $task->id,
            'employee_id' => $employee->id,
        ]);

        $service = app(TaskEvaluationService::class);

        $service->upsertEvaluation($evaluatorOne, $task, 8, 'Good');
        $service->upsertEvaluation($evaluatorOne, $task, 9, 'Updated');

        $this->assertDatabaseCount('employee_month_task_evaluations', 1);
        $this->assertDatabaseHas('employee_month_task_evaluations', [
            'task_id' => $task->id,
            'evaluator_user_id' => $evaluatorOne->id,
            'score' => 9,
            'note' => 'Updated',
        ]);

        $this->expectException(RuntimeException::class);

        $service->upsertEvaluation($evaluatorTwo, $task, 7, 'Should fail');
    }

    public function test_task_score_metrics_exclude_unevaluated_tasks(): void
    {
        $creator = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $evaluator = User::factory()->create([
            'role' => 'user',
            'must_change_password' => false,
        ]);

        $employeeOne = Employee::factory()->create(['is_active' => true]);
        $employeeTwo = Employee::factory()->create(['is_active' => true]);

        $month = (int) now()->month;
        $year = (int) now()->year;
        [$start, $end] = PayrollPeriod::resolve($month, $year);

        $taskOne = EmployeeMonthTask::query()->create([
            'title' => 'Task 1',
            'period_month' => $month,
            'period_year' => $year,
            'period_start_date' => $start->toDateString(),
            'period_end_date' => $end->toDateString(),
            'created_by' => $creator->id,
            'is_active' => true,
        ]);

        $taskTwo = EmployeeMonthTask::query()->create([
            'title' => 'Task 2',
            'period_month' => $month,
            'period_year' => $year,
            'period_start_date' => $start->toDateString(),
            'period_end_date' => $end->toDateString(),
            'created_by' => $creator->id,
            'is_active' => true,
        ]);

        EmployeeMonthTaskAssignment::query()->create(['task_id' => $taskOne->id, 'employee_id' => $employeeOne->id]);
        EmployeeMonthTaskAssignment::query()->create(['task_id' => $taskOne->id, 'employee_id' => $employeeTwo->id]);
        EmployeeMonthTaskAssignment::query()->create(['task_id' => $taskTwo->id, 'employee_id' => $employeeOne->id]);

        EmployeeMonthTaskEvaluation::query()->create([
            'task_id' => $taskOne->id,
            'evaluator_user_id' => $evaluator->id,
            'score' => 8,
            'note' => null,
        ]);

        $service = app(EmployeeTaskScoreService::class);
        $metrics = $service->getMonthlyTaskMetrics($month, $year, [$employeeOne->id, $employeeTwo->id]);

        $this->assertSame(2, $metrics['period_totals']['tasks_count']);
        $this->assertSame(1, $metrics['period_totals']['evaluated_tasks_count']);
        $this->assertSame(50.0, $metrics['period_totals']['coverage_ratio']);

        $employeeOneMetrics = $metrics['by_employee']->get($employeeOne->id);
        $employeeTwoMetrics = $metrics['by_employee']->get($employeeTwo->id);

        $this->assertSame(2, $employeeOneMetrics['assigned_tasks_count']);
        $this->assertSame(1, $employeeOneMetrics['evaluated_tasks_count']);
        $this->assertSame(8.0, $employeeOneMetrics['task_score_raw']);

        $this->assertSame(1, $employeeTwoMetrics['assigned_tasks_count']);
        $this->assertSame(1, $employeeTwoMetrics['evaluated_tasks_count']);
        $this->assertSame(8.0, $employeeTwoMetrics['task_score_raw']);
    }
}
