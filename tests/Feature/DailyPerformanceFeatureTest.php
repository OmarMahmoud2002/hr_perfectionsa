<?php

namespace Tests\Feature;

use App\Models\DailyPerformanceEntry;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DailyPerformanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_daily_performance_entry_with_attachments(): void
    {
        Storage::fake('public');

        [$employeeUser, $employee] = $this->createEmployeeUser('Emp Daily 1', 'AC-DP-1');

        $response = $this->actingAs($employeeUser)->post(route('daily-performance.employee.upsert'), [
            'work_date' => '2026-03-29',
            'project_name' => 'Attendance Reports',
            'work_description' => 'Implemented daily performance report widgets.',
            'attachments' => [
                UploadedFile::fake()->image('proof.png'),
                UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('daily_performance_entries', [
            'employee_id' => $employee->id,
            'work_date' => '2026-03-29',
            'project_name' => 'Attendance Reports',
        ]);

        $entry = DailyPerformanceEntry::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', '2026-03-29')
            ->firstOrFail();

        $this->assertDatabaseCount('daily_performance_attachments', 2);

        foreach ($entry->attachments as $attachment) {
            $this->assertTrue(Storage::disk($attachment->disk)->exists($attachment->path));
        }
    }

    public function test_employee_upsert_updates_same_day_entry_instead_of_creating_duplicate(): void
    {
        [$employeeUser, $employee] = $this->createEmployeeUser('Emp Daily 2', 'AC-DP-2');

        $this->actingAs($employeeUser)->post(route('daily-performance.employee.upsert'), [
            'work_date' => '2026-03-29',
            'project_name' => 'Project Alpha',
            'work_description' => 'Initial update.',
        ])->assertRedirect();

        $this->actingAs($employeeUser)->post(route('daily-performance.employee.upsert'), [
            'work_date' => '2026-03-29',
            'project_name' => 'Project Alpha Updated',
            'work_description' => 'Updated work summary.',
        ])->assertRedirect();

        $this->assertDatabaseCount('daily_performance_entries', 1);
        $this->assertDatabaseHas('daily_performance_entries', [
            'employee_id' => $employee->id,
            'work_date' => '2026-03-29',
            'project_name' => 'Project Alpha Updated',
            'work_description' => 'Updated work summary.',
        ]);
    }

    public function test_reviewer_roles_can_access_daily_performance_review_page(): void
    {
        foreach (['admin', 'manager', 'hr', 'user'] as $role) {
            $reviewer = User::factory()->create([
                'role' => $role,
                'must_change_password' => false,
            ]);

            $this->actingAs($reviewer)
                ->get(route('daily-performance.review.index'))
                ->assertOk();
        }
    }

    public function test_reviewer_can_upsert_daily_review(): void
    {
        $reviewer = User::factory()->create([
            'role' => 'user',
            'must_change_password' => false,
        ]);

        [, $employee] = $this->createEmployeeUser('Emp Daily 3', 'AC-DP-3');

        $entry = DailyPerformanceEntry::query()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-29',
            'project_name' => 'Payroll Improvements',
            'work_description' => 'Completed payroll refactor.',
            'submitted_at' => now(),
        ]);

        $this->actingAs($reviewer)
            ->post(route('daily-performance.review.upsert', $entry), [
                'rating' => 4,
                'comment' => 'Great progress.',
            ])
            ->assertRedirect();

        $this->actingAs($reviewer)
            ->post(route('daily-performance.review.upsert', $entry), [
                'rating' => 5,
                'comment' => 'Excellent follow-up.',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('daily_performance_reviews', 1);
        $this->assertDatabaseHas('daily_performance_reviews', [
            'entry_id' => $entry->id,
            'reviewer_user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'Excellent follow-up.',
        ]);
    }

    public function test_employee_can_see_reviews_on_his_daily_performance_page(): void
    {
        [$employeeUser, $employee] = $this->createEmployeeUser('Emp Daily 4', 'AC-DP-4');

        $reviewer = User::factory()->create([
            'name' => 'Reviewer Name',
            'role' => 'user',
            'must_change_password' => false,
        ]);

        $entry = DailyPerformanceEntry::query()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-29',
            'project_name' => 'Dashboard Upgrade',
            'work_description' => 'Delivered responsive dashboard updates.',
            'submitted_at' => now(),
        ]);

        $entry->reviews()->create([
            'reviewer_user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'عمل ممتاز ومنظم.',
            'reviewed_at' => now(),
        ]);

        $this->actingAs($employeeUser)
            ->get(route('daily-performance.employee.index', ['date' => '2026-03-29']))
            ->assertOk()
            ->assertSee('Reviewer Name')
            ->assertSee('عمل ممتاز ومنظم.');
    }

    public function test_user_role_cannot_access_employee_daily_performance_page(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'must_change_password' => false,
        ]);

        $this->actingAs($user)
            ->get(route('daily-performance.employee.index'))
            ->assertStatus(403);
    }

    public function test_employee_role_cannot_access_review_page(): void
    {
        [$employeeUser] = $this->createEmployeeUser('Emp Daily 5', 'AC-DP-5');

        $this->actingAs($employeeUser)
            ->get(route('daily-performance.review.index'))
            ->assertStatus(403);
    }

    public function test_guest_cannot_access_daily_performance_routes(): void
    {
        $this->get(route('daily-performance.employee.index'))
            ->assertRedirect(route('login'));

        $this->get(route('daily-performance.review.index'))
            ->assertRedirect(route('login'));
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
