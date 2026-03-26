<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeMonthTask;
use App\Models\EmployeeMonthTaskAssignment;
use App\Models\EmployeeMonthTaskEvaluation;
use App\Models\EmployeeMonthVote;
use App\Models\User;
use App\Services\Payroll\PayrollPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageEExportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_employee_of_month_excel(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        [$voter] = $this->createEmployeeUser('Export Voter', 'AC-EV');
        [, $candidate] = $this->createEmployeeUser('Export Candidate', 'AC-EC');

        $month = (int) now()->month;
        $year = (int) now()->year;

        EmployeeMonthVote::query()->create([
            'voter_user_id' => $voter->id,
            'voted_employee_id' => $candidate->id,
            'vote_month' => $month,
            'vote_year' => $year,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('employee-of-month.admin.export', ['month' => $month, 'year' => $year]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_can_download_tasks_evaluations_excel(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $evaluator = User::factory()->create([
            'role' => 'user',
            'must_change_password' => false,
        ]);

        $employee = Employee::factory()->create(['is_active' => true]);

        $month = (int) now()->month;
        $year = (int) now()->year;
        [$periodStart, $periodEnd] = PayrollPeriod::resolve($month, $year);

        $task = EmployeeMonthTask::query()->create([
            'title' => 'Export Task',
            'description' => 'Task for export test',
            'period_month' => $month,
            'period_year' => $year,
            'period_start_date' => $periodStart->toDateString(),
            'period_end_date' => $periodEnd->toDateString(),
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        EmployeeMonthTaskAssignment::query()->create([
            'task_id' => $task->id,
            'employee_id' => $employee->id,
        ]);

        EmployeeMonthTaskEvaluation::query()->create([
            'task_id' => $task->id,
            'evaluator_user_id' => $evaluator->id,
            'score' => 8,
            'note' => 'Great output',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('tasks.admin.export', ['month' => $month, 'year' => $year]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
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
