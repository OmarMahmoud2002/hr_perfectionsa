<?php

namespace App\Services\Notifications;

use App\Models\EmployeeMonthTask;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\EmployeeOfMonthPublishedNotification;
use App\Notifications\LeaveRequestDecisionNotification;
use App\Notifications\LeaveRequestSubmittedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\WelcomeEmployeeNotification;
use Illuminate\Support\Collection;

class EmailNotificationService
{
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
                $user->notify(new LeaveRequestSubmittedNotification($leaveRequest));
            });
    }

    public function notifyLeaveDecision(LeaveRequest $leaveRequest, User $actor, string $decision): void
    {
        $leaveRequest->loadMissing('employee.user');

        $employeeUser = $leaveRequest->employee?->user;
        if (! $employeeUser) {
            return;
        }

        $employeeUser->notify(new LeaveRequestDecisionNotification(
            $leaveRequest,
            $actor->name,
            $decision,
        ));
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

        $users->each(fn (User $user) => $user->notify(new TaskAssignedNotification($task)));
    }

    public function sendWelcomeOnFirstEmail(User $user, ?string $oldEmail): void
    {
        if ($this->hasEmail($oldEmail)) {
            return;
        }

        if (! $this->canReceiveMail($user)) {
            return;
        }

        $user->notify(new WelcomeEmployeeNotification());
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

        $user->notify(new WelcomeEmployeeNotification());
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
            $user->notify(new EmployeeOfMonthPublishedNotification($month, $year, $winnerName));
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
}
