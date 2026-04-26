<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeOfMonthPublication;
use App\Models\EmployeeOfMonthResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_account_page_is_displayed(): void
    {
        $employee = Employee::factory()->create([
            'name' => 'Test Employee',
            'job_title' => 'developer',
        ]);

        $user = User::factory()->create([
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get(route('account.my'));

        $response->assertOk();
    }

    public function test_my_account_profile_information_can_be_updated(): void
    {
        $employee = Employee::factory()->create([
            'name' => 'Test Employee',
            'job_title' => 'developer',
        ]);

        $user = User::factory()->create([
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => false,
            'email' => 'test.employee@perfection.com',
        ]);

        $response = $this->actingAs($user)->put(route('account.my.update'), [
            'name' => 'Updated Employee Name',
            'bio' => 'Backend developer',
            'social_link_1' => 'https://linkedin.com/in/test-employee',
            'social_link_2' => 'https://github.com/test-employee',
            'email' => 'updated.employee@perfection.com',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('Updated Employee Name', $user->fresh()->name);
        $this->assertSame('updated.employee@perfection.com', $user->fresh()->email);
        $this->assertSame('Updated Employee Name', $employee->fresh()->name);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'Backend developer',
            'social_link_1' => 'https://linkedin.com/in/test-employee',
            'social_link_2' => 'https://github.com/test-employee',
        ]);
    }

    public function test_my_account_shows_employee_of_month_achievements_count_and_months(): void
    {
        $employee = Employee::factory()->create([
            'name' => 'Winner Employee',
            'job_title' => 'developer',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => false,
        ]);

        $otherEmployee = Employee::factory()->create([
            'name' => 'Other Employee',
            'job_title' => 'developer',
            'is_active' => true,
        ]);

        // 2026-01 winner: current employee.
        EmployeeOfMonthResult::create([
            'employee_id' => $employee->id,
            'month' => 1,
            'year' => 2026,
            'final_score' => 91.25,
            'breakdown' => [
                'task_points' => 36,
            ],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);
        EmployeeOfMonthPublication::create([
            'month' => 1,
            'year' => 2026,
            'published_at' => now(),
            'published_by_user_id' => $user->id,
        ]);
        EmployeeOfMonthResult::create([
            'employee_id' => $otherEmployee->id,
            'month' => 1,
            'year' => 2026,
            'final_score' => 83.00,
            'breakdown' => [
                'task_points' => 30,
            ],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);

        // 2026-02 winner: tie on final score, resolved by higher task_points for current employee.
        EmployeeOfMonthResult::create([
            'employee_id' => $employee->id,
            'month' => 2,
            'year' => 2026,
            'final_score' => 88.00,
            'breakdown' => [
                'task_points' => 34,
            ],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);
        EmployeeOfMonthPublication::create([
            'month' => 2,
            'year' => 2026,
            'published_at' => now(),
            'published_by_user_id' => $user->id,
        ]);
        EmployeeOfMonthResult::create([
            'employee_id' => $otherEmployee->id,
            'month' => 2,
            'year' => 2026,
            'final_score' => 88.00,
            'breakdown' => [
                'task_points' => 28,
            ],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);

        // 2026-03 winner: other employee.
        EmployeeOfMonthResult::create([
            'employee_id' => $otherEmployee->id,
            'month' => 3,
            'year' => 2026,
            'final_score' => 92.10,
            'breakdown' => [
                'task_points' => 35,
            ],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);
        EmployeeOfMonthPublication::create([
            'month' => 3,
            'year' => 2026,
            'published_at' => now(),
            'published_by_user_id' => $user->id,
        ]);
        EmployeeOfMonthResult::create([
            'employee_id' => $employee->id,
            'month' => 3,
            'year' => 2026,
            'final_score' => 90.90,
            'breakdown' => [
                'task_points' => 33,
            ],
            'formula_version' => 'v3_weighted_points',
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('account.my'));

        $response->assertOk();
        $response->assertSee('الإنجازات');
        $response->assertSee('عدد مرات الفوز');
        $response->assertSee('2');
        $response->assertSee('يناير 2026');
        $response->assertSee('فبراير 2026');
    }
}
