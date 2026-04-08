<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeOfMonthPublication;
use App\Models\EmployeeOfMonthResult;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeOfMonthPublishRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_vote_page_shows_only_top_four_above_minimum_score_after_publication(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        [$viewer] = $this->createEmployeeUser('Viewer', 'AC-VIEW-TOP4');

        $employees = collect([
            ['Winner One', 'AC-W1', 96.0],
            ['Winner Two', 'AC-W2', 95.0],
            ['Winner Three', 'AC-W3', 94.0],
            ['Winner Four', 'AC-W4', 93.0],
            ['Winner Five', 'AC-W5', 92.0],
            ['Low Score', 'AC-L1', 89.0],
        ])->map(function (array $row) {
            [, $employee] = $this->createEmployeeUser($row[0], $row[1]);

            return [
                'employee' => $employee,
                'score' => $row[2],
            ];
        });

        foreach ($employees as $item) {
            EmployeeOfMonthResult::create([
                'employee_id' => $item['employee']->id,
                'month' => 2,
                'year' => 2026,
                'final_score' => $item['score'],
                'breakdown' => ['task_points' => 30],
                'formula_version' => 'v3_weighted_points',
                'generated_at' => now(),
            ]);
        }

        $employees
            ->filter(fn (array $item) => $item['score'] <= 92.0)
            ->each(fn (array $item) => $item['employee']->update(['is_active' => false]));

        EmployeeOfMonthPublication::query()->create([
            'month' => 2,
            'year' => 2026,
            'published_at' => now(),
            'published_by_user_id' => $viewer->id,
        ]);

        $response = $this->actingAs($viewer)->get(route('employee-of-month.vote.page'));

        $response->assertOk();
        $response->assertSee('Winner One');
        $response->assertSee('Winner Two');
        $response->assertSee('Winner Three');
        $response->assertSee('Winner Four');
        $response->assertDontSee('Winner Five');
        $response->assertDontSee('Low Score');
    }

    public function test_vote_page_hides_previous_results_when_not_published(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0, config('app.timezone')));

        [$viewer] = $this->createEmployeeUser('Viewer No Publish', 'AC-NP');
        [, $winner] = $this->createEmployeeUser('Hidden Winner', 'AC-HW');
        $winner->update(['is_active' => false]);

        EmployeeOfMonthResult::create([
            'employee_id' => $winner->id,
            'month' => 2,
            'year' => 2026,
            'final_score' => 95.0,
            'breakdown' => ['task_points' => 30],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($viewer)->get(route('employee-of-month.vote.page'));

        $response->assertOk();
        $response->assertSee('نتائج الشهر الماضي لم تُنشر بعد من الإدارة.');
        $response->assertDontSee('Hidden Winner');
    }

    private function createEmployeeUser(string $name, string $acNo): array
    {
        $employee = Employee::factory()->create([
            'name' => $name,
            'ac_no' => $acNo,
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
