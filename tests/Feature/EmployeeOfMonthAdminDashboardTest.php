<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeMonthAdminScore;
use App\Models\EmployeeOfMonthResult;
use App\Models\EmployeeMonthVote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeOfMonthAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_dashboard_page_loads_with_sections(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        [$voter] = $this->createEmployeeUser('Emp Admin 1', 'AC-A1');
        [, $candidate] = $this->createEmployeeUser('Emp Admin 2', 'AC-A2');

        EmployeeMonthVote::create([
            'voter_user_id' => $voter->id,
            'voted_employee_id' => $candidate->id,
            'vote_month' => 3,
            'vote_year' => 2026,
        ]);

        $response = $this->actingAs($admin)->get(route('employee-of-month.admin.index', [
            'month' => 3,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('نتائج التصويت');
        $response->assertSee('المراكز الأربعة الأولى');
        $response->assertSee('نقاط التصويت');
        $response->assertSee('Explain Score');
        $response->assertSee('الترتيب النهائي حسب المعادلة');
        $response->assertSee('History النتائج الشهرية');
    }

    public function test_employee_cannot_access_admin_dashboard(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        [$employeeUser] = $this->createEmployeeUser('Emp Blocked', 'AC-EB');

        $this->actingAs($employeeUser)
            ->get(route('employee-of-month.admin.index'))
            ->assertStatus(403);
    }

    public function test_admin_can_upsert_admin_score(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        [, $employee] = $this->createEmployeeUser('Score Target', 'AC-ST');

        $this->actingAs($admin)
            ->post(route('employee-of-month.admin.score.upsert'), [
                'employee_id' => $employee->id,
                'month' => 3,
                'year' => 2026,
                'score' => 5,
                'note' => 'Excellent discipline',
            ])
            ->assertRedirect(route('employee-of-month.admin.index', ['month' => 3, 'year' => 2026]));

        $this->assertDatabaseHas('employee_month_admin_scores', [
            'employee_id' => $employee->id,
            'month' => 3,
            'year' => 2026,
            'score' => 5,
            'created_by' => $admin->id,
        ]);

        $this->assertSame(1, EmployeeMonthAdminScore::count());
    }

    public function test_admin_can_finalize_month_and_create_history(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        [$voter] = $this->createEmployeeUser('Finalize 1', 'AC-F1');
        [, $candidate] = $this->createEmployeeUser('Finalize 2', 'AC-F2');

        EmployeeMonthVote::create([
            'voter_user_id' => $voter->id,
            'voted_employee_id' => $candidate->id,
            'vote_month' => 3,
            'vote_year' => 2026,
        ]);

        $this->actingAs($admin)
            ->post(route('employee-of-month.admin.finalize'), [
                'month' => 3,
                'year' => 2026,
            ])
            ->assertRedirect(route('employee-of-month.admin.index', ['month' => 3, 'year' => 2026]));

        $this->assertGreaterThan(0, EmployeeOfMonthResult::count());
        $this->assertDatabaseHas('employee_of_month_results', [
            'month' => 3,
            'year' => 2026,
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
