<?php

namespace App\Services\Notifications;

use App\Models\DailyPerformanceEntry;
use App\Models\EmployeeMonthTask;
use App\Models\EmployeeMonthTaskAssignment;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\AttendanceMonthImportedNotification;
use App\Notifications\DailyPerformanceReviewedNotification;
use App\Notifications\EmployeeOfMonthPublishedNotification;
use App\Notifications\LeaveRequestDecisionNotification;
use App\Notifications\LeaveRequestSubmittedNotification;
use App\Notifications\TaskCompletedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskEvaluationSubmittedNotification;
use App\Notifications\WelcomeEmployeeNotification;
use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmailNotificationService
{
    public function __construct(
        private readonly NotificationInfrastructureService $notificationInfrastructureService,
    ) {}

    public function notifyLeaveRequestSubmitted(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->loadMissing('employee.user', 'managerEmployee.user');

        $recipients = collect();

        if ($leaveRequest->hr_status === 'pending') {
            $hrLike = User::query()
                ->whereIn('role', ['admin', 'hr'])
                ->get();

            $recipients = $recipients->merge($hrLike);
        }

        if ($leaveRequest->manager_status === 'pending') {
            if ($leaveRequest->managerEmployee?->user !== null) {
                $recipients->push($leaveRequest->managerEmployee->user);
            }

            $managers = User::query()
                ->where('role', 'manager')
                ->get();

            $recipients = $recipients->merge($managers);
        }

        $leaveRequest->loadMissing('employee');

        $recipients
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->each(function (User $user) use ($leaveRequest): void {
                $this->safeNotify($user, new LeaveRequestSubmittedNotification($leaveRequest), [
                    'event' => 'leave_request_submitted',
                    'leave_request_id' => (int) $leaveRequest->id,
                ]);
            });
    }

    public function notifyLeaveDecision(LeaveRequest $leaveRequest, User $actor, string $decision): void
    {
        $leaveRequest->loadMissing('employee.user');

        $employeeUser = $leaveRequest->employee?->user;
        if (! $employeeUser) {
            return;
        }

        $this->safeNotify($employeeUser, new LeaveRequestDecisionNotification(
            $leaveRequest,
            $actor->name,
            $decision,
        ), [
            'event' => 'leave_request_decision',
            'leave_request_id' => (int) $leaveRequest->id,
        ]);
    }

    /**
     * @param array<int, int> $employeeIds
     */
    public function notifyTaskAssigned(EmployeeMonthTask $task, array $employeeIds): void
    {
        $sanitized = collect($employeeIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($sanitized)) {
            return;
        }

        $task->loadMissing('creator');

        $users = User::query()
            ->whereIn('employee_id', $sanitized)
            ->get();

        $users->each(fn (User $user) => $this->safeNotify($user, new TaskAssignedNotification($task), [
            'event' => 'task_assigned',
            'task_id' => (int) $task->id,
        ]));
    }

    public function notifyTaskCompleted(EmployeeMonthTaskAssignment $assignment, User $actor): void
    {
        $assignment->loadMissing('task', 'employee');

        $notification = new TaskCompletedNotification(
            $assignment->task,
            $assignment->employee,
            (string) $actor->name,
        );

        User::query()
            ->whereIn('role', ['user', 'admin', 'hr'])
            ->get()
            ->each(fn (User $user) => $this->safeNotify($user, $notification, [
                'event' => 'task_completed',
                'task_id' => (int) $assignment->task_id,
            ]));
    }

    public function notifyTaskEvaluated(EmployeeMonthTask $task, User $evaluator, float $score, ?string $note = null): void
    {
        $employeeIds = $task->employees()
            ->pluck('employees.id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($employeeIds === []) {
            return;
        }

        $notification = new TaskEvaluationSubmittedNotification(
            $task,
            (string) $evaluator->name,
            $score,
            $note,
        );

        User::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('role', User::workforceRoles())
            ->get()
            ->each(fn (User $user) => $this->safeNotify($user, $notification, [
                'event' => 'task_evaluated',
                'task_id' => (int) $task->id,
            ]));
    }

    public function notifyDailyPerformanceReviewed(DailyPerformanceEntry $entry, User $reviewer, int $rating, ?string $comment = null): void
    {
        $entry->loadMissing('employee.user');

        $employeeUser = $entry->employee?->user;
        if (! $employeeUser instanceof User) {
            return;
        }

        $this->safeNotify($employeeUser, new DailyPerformanceReviewedNotification(
            $entry,
            (string) $reviewer->name,
            $rating,
            $comment,
        ), [
            'event' => 'daily_performance_reviewed',
            'entry_id' => (int) $entry->id,
        ]);
    }

    public function notifyAttendanceMonthImported(int $month, int $year): void
    {
        $notification = new AttendanceMonthImportedNotification($month, $year);

        User::query()
            ->whereIn('role', User::workforceRoles())
            ->get()
            ->each(fn (User $user) => $this->safeNotify($user, $notification, [
                'event' => 'attendance_month_imported',
                'month' => $month,
                'year' => $year,
            ]));
    }

    public function sendWelcomeOnFirstEmail(User $user, ?string $oldEmail): void
    {
        if ($this->hasEmail($oldEmail)) {
            return;
        }

        if (! $this->canReceiveMail($user)) {
            return;
        }

        $this->safeNotify($user, new WelcomeEmployeeNotification(), [
            'event' => 'welcome_employee_first_email',
        ]);
    }

    public function sendWelcomeOnAssignedEmail(User $user, ?string $oldEmail): void
    {
        if (! $this->canReceiveMail($user)) {
            return;
        }

        $currentEmail = strtolower(trim((string) $user->email));
        $previousEmail = is_string($oldEmail) ? strtolower(trim($oldEmail)) : '';

        if ($currentEmail === '' || $currentEmail === $previousEmail) {
            return;
        }

        $this->safeNotify($user, new WelcomeEmployeeNotification(), [
            'event' => 'welcome_employee_assigned_email',
        ]);
    }

    public function notifyEmployeeOfMonthPublished(int $month, int $year, ?int $winnerEmployeeId = null): void
    {
        $winnerName = null;
        if ($winnerEmployeeId !== null && $winnerEmployeeId > 0) {
            $winnerName = Employee::query()
                ->whereKey($winnerEmployeeId)
                ->value('name');
        }

        $users = User::query()
            ->whereIn('role', User::workforceRoles())
            ->get();

        $users->each(function (User $user) use ($month, $year, $winnerName): void {
            $this->safeNotify($user, new EmployeeOfMonthPublishedNotification($month, $year, $winnerName), [
                'event' => 'employee_of_month_published',
                'month' => $month,
                'year' => $year,
            ]);
        });
    }

    private function canReceiveMail(User $user): bool
    {
        return $this->hasEmail($user->email);
    }

    private function hasEmail(?string $email): bool
    {
        return is_string($email) && trim($email) !== '';
    }

    private function safeNotify(User $user, LaravelNotification $notification, array $context = []): bool
    {
        if (! $this->notificationInfrastructureService->ensureNotificationTablesExist()) {
            return false;
        }

        try {
            $user->notify($notification);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Notification delivery skipped after the main action succeeded.', array_merge($context, [
                'user_id' => (int) $user->id,
                'notification' => $notification::class,
                'message' => $exception->getMessage(),
            ]));

            return false;
        }
    }
}
