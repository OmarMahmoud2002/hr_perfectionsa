<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeOfMonthResult;
use App\Models\EmployeeMonthVote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeOfMonthVoteEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_employee_can_get_vote_status(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        [$user] = $this->createEmployeeUser('Voter One', 'AC-V1');

        $response = $this->actingAs($user)->getJson(route('employee-of-month.vote.status'));

        $response->assertOk()
            ->assertJson([
                'month' => 3,
                'year' => 2026,
                'has_voted' => false,
                'can_vote' => true,
                'reason' => 'ok',
            ])
            ->assertJsonStructure([
                'voted_employee_id',
                'voting_closes_at',
                'seconds_remaining_to_close',
            ]);
    }

    public function test_employee_can_open_vote_page_and_not_see_self_as_candidate(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        [$voterUser, $voterEmployee] = $this->createEmployeeUser('Voter Page', 'AC-VP');
        [, $candidate] = $this->createEmployeeUser('Candidate Page', 'AC-CP');

        $response = $this->actingAs($voterUser)->get(route('employee-of-month.vote.page'));

        $response->assertOk();
        $response->assertSee($candidate->name);
        $response->assertDontSee($voterEmployee->ac_no);
    }

    public function test_vote_page_shows_previous_month_top_three_and_title_holder_for_employees(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        [$viewerUser] = $this->createEmployeeUser('Viewer Employee', 'AC-VIEW');
        [, $winnerOne] = $this->createEmployeeUser('Winner One', 'AC-W1');
        [, $winnerTwo] = $this->createEmployeeUser('Winner Two', 'AC-W2');
        [, $winnerThree] = $this->createEmployeeUser('Winner Three', 'AC-W3');

        // Previous payroll month for March 2026 is February 2026.
        EmployeeOfMonthResult::create([
            'employee_id' => $winnerOne->id,
            'month' => 2,
            'year' => 2026,
            'final_score' => 92.00,
            'breakdown' => ['task_points' => 35],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);

        EmployeeOfMonthResult::create([
            'employee_id' => $winnerTwo->id,
            'month' => 2,
            'year' => 2026,
            'final_score' => 89.50,
            'breakdown' => ['task_points' => 32],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);

        EmployeeOfMonthResult::create([
            'employee_id' => $winnerThree->id,
            'month' => 2,
            'year' => 2026,
            'final_score' => 88.20,
            'breakdown' => ['task_points' => 30],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($viewerUser)->get(route('employee-of-month.vote.page'));

        $response->assertOk();
        $response->assertSee('أوائل الشهر الماضي');
        $response->assertSee('فبراير 2026');
        $response->assertSee($winnerOne->name);
        $response->assertSee($winnerTwo->name);
        $response->assertSee($winnerThree->name);
        $response->assertSee('حامل اللقب الحالي');
    }

    public function test_employee_can_submit_vote_and_receive_payload(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        [$voterUser] = $this->createEmployeeUser('Voter Two', 'AC-V2');
        [, $candidate] = $this->createEmployeeUser('Candidate Two', 'AC-C2');

        $response = $this->actingAs($voterUser)->postJson(route('employee-of-month.vote.store'), [
            'voted_employee_id' => $candidate->id,
        ]);

        $response->assertCreated()
            ->assertJson([
                'status' => 'created',
                'has_voted' => true,
                'voted_employee_id' => $candidate->id,
            ]);

        $this->assertDatabaseHas('employee_month_votes', [
            'voter_user_id' => $voterUser->id,
            'voted_employee_id' => $candidate->id,
            'vote_month' => 3,
            'vote_year' => 2026,
        ]);
    }

    public function test_duplicate_vote_returns_already_voted_without_new_row(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        [$voterUser] = $this->createEmployeeUser('Voter Three', 'AC-V3');
        [, $candidate] = $this->createEmployeeUser('Candidate Three', 'AC-C3');

        EmployeeMonthVote::create([
            'voter_user_id' => $voterUser->id,
            'voted_employee_id' => $candidate->id,
            'vote_month' => 3,
            'vote_year' => 2026,
        ]);

        $response = $this->actingAs($voterUser)->postJson(route('employee-of-month.vote.store'), [
            'voted_employee_id' => $candidate->id,
        ]);

        $response->assertOk()->assertJson([
            'status' => 'already_voted',
            'has_voted' => true,
            'voted_employee_id' => $candidate->id,
        ]);

        $this->assertDatabaseCount('employee_month_votes', 1);
    }

    public function test_vote_cycle_rolls_over_on_day_twenty_two_and_remains_open(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 22, 10, 0, 0, config('app.timezone')));

        [$voterUser] = $this->createEmployeeUser('Voter Four', 'AC-V4');
        [, $candidate] = $this->createEmployeeUser('Candidate Four', 'AC-C4');

        $response = $this->actingAs($voterUser)->postJson(route('employee-of-month.vote.store'), [
            'voted_employee_id' => $candidate->id,
        ]);

        $response->assertCreated()
            ->assertJson([
                'status' => 'created',
                'has_voted' => true,
                'voted_employee_id' => $candidate->id,
            ]);

        $this->assertDatabaseHas('employee_month_votes', [
            'voter_user_id' => $voterUser->id,
            'voted_employee_id' => $candidate->id,
            'vote_month' => 4,
            'vote_year' => 2026,
        ]);
    }

    public function test_admin_can_access_vote_status_but_is_marked_ineligible(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)
            ->getJson(route('employee-of-month.vote.status'))
            ->assertOk()
            ->assertJson([
                'can_vote' => false,
                'reason' => 'ineligible_voter',
            ]);
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
