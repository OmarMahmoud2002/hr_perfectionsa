<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeMonthTask;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Services\Notifications\EmailNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_request_submission_creates_database_notifications_for_recipients_without_email(): void
    {
        $employee = Employee::factory()->create(['name' => 'Leave Employee']);
        $managerEmployee = Employee::factory()->create(['name' => 'Department Manager']);

        $hrUser = User::factory()->create([
            'role' => 'hr',
            'email' => null,
            'must_change_password' => false,
        ]);

        $managerUser = User::factory()->create([
            'role' => 'manager',
            'email' => null,
            'employee_id' => $managerEmployee->id,
            'must_change_password' => false,
        ]);

        $leaveRequest = LeaveRequest::query()->create([
            'employee_id' => $employee->id,
            'department_id' => null,
            'manager_employee_id' => $managerEmployee->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'requested_days' => 2,
            'reason' => 'Travel',
            'status' => 'pending',
            'hr_status' => 'pending',
            'manager_status' => 'pending',
            'submitted_at' => now(),
        ]);

        app(EmailNotificationService::class)->notifyLeaveRequestSubmitted($leaveRequest);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $hrUser->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $managerUser->id,
        ]);
    }

    public function test_task_assignment_uses_mail_and_database_channels_for_users_with_email(): void
    {
        Notification::fake();

        $creator = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $employee = Employee::factory()->create(['name' => 'Task Employee']);
        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'employee_id' => $employee->id,
            'email' => 'task.employee@example.com',
            'must_change_password' => false,
        ]);

        $task = EmployeeMonthTask::query()->create([
            'title' => 'Prepare monthly summary',
            'description' => 'Collect the attendance highlights.',
            'period_month' => (int) now()->month,
            'period_year' => (int) now()->year,
            'period_start_date' => now()->startOfMonth()->toDateString(),
            'period_end_date' => now()->endOfMonth()->toDateString(),
            'task_date' => now()->toDateString(),
            'created_by' => $creator->id,
            'is_active' => true,
        ]);

        app(EmailNotificationService::class)->notifyTaskAssigned($task, [$employee->id]);

        Notification::assertSentTo(
            $employeeUser,
            TaskAssignedNotification::class,
            function (TaskAssignedNotification $notification, array $channels): bool {
                return in_array('database', $channels, true) && in_array('mail', $channels, true);
            }
        );
    }

    public function test_employee_directory_can_filter_accounts_that_need_email_follow_up(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $readyEmployee = Employee::factory()->create(['name' => 'Ready Employee']);
        User::factory()->create([
            'role' => 'employee',
            'employee_id' => $readyEmployee->id,
            'email' => 'ready@example.com',
            'must_change_password' => false,
        ]);

        $missingEmailEmployee = Employee::factory()->create(['name' => 'Missing Email Employee']);
        User::factory()->create([
            'role' => 'employee',
            'employee_id' => $missingEmailEmployee->id,
            'email' => null,
            'must_change_password' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('employees.index', ['email_status' => 'missing']))
            ->assertOk()
            ->assertSee('Missing Email Employee')
            ->assertDontSee('Ready Employee');
    }

    public function test_employee_directory_defaults_to_twenty_items_per_page_and_supports_custom_per_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        Employee::factory()->count(25)->create();

        $defaultResponse = $this->actingAs($admin)
            ->get(route('employees.index'));

        $defaultResponse->assertOk();
        $defaultPaginator = $defaultResponse->viewData('employees');
        $this->assertSame(20, $defaultPaginator->perPage());
        $this->assertCount(20, $defaultPaginator->items());

        $customResponse = $this->actingAs($admin)
            ->get(route('employees.index', ['per_page' => 50]));

        $customResponse->assertOk();
        $customPaginator = $customResponse->viewData('employees');
        $this->assertSame(50, $customPaginator->perPage());
        $this->assertCount(25, $customPaginator->items());
    }

    public function test_notifications_page_can_mark_items_as_read(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $notificationId = (string) Str::uuid();

        $user->notifications()->create([
            'id' => $notificationId,
            'type' => TaskAssignedNotification::class,
            'data' => [
                'title' => 'Task ready',
                'message' => 'A task was assigned to you.',
                'url' => route('dashboard'),
                'type' => 'task_assigned',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Task ready');

        $this->actingAs($user)
            ->post(route('notifications.read', ['notificationId' => $notificationId]))
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->notifications()->findOrFail($notificationId)->read_at);
    }

    public function test_notifications_page_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $user->notifications()->createMany([
            [
                'id' => (string) Str::uuid(),
                'type' => TaskAssignedNotification::class,
                'data' => ['title' => 'First', 'message' => 'First message', 'url' => route('dashboard'), 'type' => 'task_assigned'],
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => TaskAssignedNotification::class,
                'data' => ['title' => 'Second', 'message' => 'Second message', 'url' => route('dashboard'), 'type' => 'task_assigned'],
            ],
        ]);

        $this->assertSame(2, $user->unreadNotifications()->count());

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}
