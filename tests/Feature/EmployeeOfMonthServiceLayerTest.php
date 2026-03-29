<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeMonthAdminScore;
use App\Models\EmployeeMonthVote;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\EmployeeOfMonth\EmployeeOfMonthFinalizationService;
use App\Services\EmployeeOfMonth\EmployeeOfMonthVoteException;
use App\Services\EmployeeOfMonth\VoteSubmissionService;
use App\Services\Payroll\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeOfMonthServiceLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_vote_submission_is_idempotent_for_duplicate_attempts(): void
    {
        [$voterUser] = $this->createEmployeeUser('Voter User', 'AC-V-1');
        [$candidateUser, $candidateEmployee] = $this->createEmployeeUser('Candidate User', 'AC-C-1');

        $service = app(VoteSubmissionService::class);

        $month = (int) now()->month;
        $year = (int) now()->year;
        $voteTime = Carbon::create($year, $month, 10, 10, 0, 0, config('app.timezone'));

        $first = $service->submit($voterUser, $candidateEmployee, $month, $year, $voteTime);
        $second = $service->submit($voterUser, $candidateEmployee, $month, $year, $voteTime);

        $this->assertSame('created', $first['status']);
        $this->assertSame('already_voted', $second['status']);

        $this->assertDatabaseCount('employee_month_votes', 1);
        $this->assertDatabaseHas('employee_month_votes', [
            'voter_user_id' => $voterUser->id,
            'voted_employee_id' => $candidateEmployee->id,
            'vote_month' => $month,
            'vote_year' => $year,
        ]);

        // Silence static analysis about intentionally unused variable.
        $this->assertNotNull($candidateUser);
    }

    public function test_vote_submission_blocks_self_vote(): void
    {
        [$voterUser, $voterEmployee] = $this->createEmployeeUser('Self User', 'AC-S-1');

        $service = app(VoteSubmissionService::class);

        $month = (int) now()->month;
        $year = (int) now()->year;
        $voteTime = Carbon::create($year, $month, 10, 10, 0, 0, config('app.timezone'));

        $this->expectException(EmployeeOfMonthVoteException::class);

        $service->submit($voterUser, $voterEmployee, $month, $year, $voteTime);
    }

    public function test_finalization_writes_history_rows_with_breakdown(): void
    {
        [$userOne, $employeeOne] = $this->createEmployeeUser('Employee One', 'AC-E-1');
        [$userTwo, $employeeTwo] = $this->createEmployeeUser('Employee Two', 'AC-E-2');

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $month = (int) now()->month;
        $year = (int) now()->year;

        $batch = ImportBatch::create([
            'file_name' => 'seed.xlsx',
            'file_path' => 'imports/seed.xlsx',
            'month' => $month,
            'year' => $year,
            'status' => 'completed',
            'records_count' => 2,
            'employees_count' => 2,
            'uploaded_by' => $admin->id,
        ]);

        [$periodStart] = PayrollPeriod::resolve($month, $year);
        $date = $periodStart->copy()->addDays(2)->toDateString();

        AttendanceRecord::create([
            'employee_id' => $employeeOne->id,
            'import_batch_id' => $batch->id,
            'date' => $date,
            'clock_in' => '09:15:00',
            'clock_out' => '17:00:00',
            'is_absent' => false,
            'late_minutes' => 30,
            'overtime_minutes' => 10,
            'work_minutes' => 460,
        ]);

        AttendanceRecord::create([
            'employee_id' => $employeeTwo->id,
            'import_batch_id' => $batch->id,
            'date' => $date,
            'clock_in' => '08:55:00',
            'clock_out' => '18:30:00',
            'is_absent' => false,
            'late_minutes' => 5,
            'overtime_minutes' => 90,
            'work_minutes' => 575,
        ]);

        EmployeeMonthVote::create([
            'voter_user_id' => $userOne->id,
            'voted_employee_id' => $employeeTwo->id,
            'vote_month' => $month,
            'vote_year' => $year,
        ]);

        EmployeeMonthVote::create([
            'voter_user_id' => $userTwo->id,
            'voted_employee_id' => $employeeTwo->id,
            'vote_month' => $month,
            'vote_year' => $year,
        ]);

        EmployeeMonthAdminScore::create([
            'employee_id' => $employeeOne->id,
            'month' => $month,
            'year' => $year,
            'score' => 4,
            'created_by' => $admin->id,
        ]);

        EmployeeMonthAdminScore::create([
            'employee_id' => $employeeTwo->id,
            'month' => $month,
            'year' => $year,
            'score' => 5,
            'created_by' => $admin->id,
        ]);

        $service = app(EmployeeOfMonthFinalizationService::class);
        $result = $service->finalizeMonth($month, $year);

        $this->assertSame(2, $result['rows_count']);
        $this->assertDatabaseCount('employee_of_month_results', 2);

        $top = $result['rows']->first();
        $this->assertSame($employeeTwo->id, $top['employee_id']);

        $this->assertDatabaseHas('employee_of_month_results', [
            'employee_id' => $employeeOne->id,
            'month' => $month,
            'year' => $year,
            'formula_version' => 'v3_weighted_points',
        ]);

        $this->assertDatabaseHas('employee_of_month_results', [
            'employee_id' => $employeeTwo->id,
            'month' => $month,
            'year' => $year,
            'formula_version' => 'v3_weighted_points',
        ]);

        $this->assertArrayHasKey('task_points', $top['breakdown']);
        $this->assertArrayHasKey('vote_points', $top['breakdown']);
        $this->assertArrayHasKey('work_hours_points', $top['breakdown']);
        $this->assertArrayHasKey('punctuality_points', $top['breakdown']);
        $this->assertArrayHasKey('final_points', $top['breakdown']);
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
