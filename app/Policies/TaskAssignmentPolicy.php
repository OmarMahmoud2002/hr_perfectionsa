<?php

namespace App\Policies;

use App\Models\EmployeeMonthTask;
use App\Models\User;

class TaskAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminLike() || $user->isDepartmentManager();
    }

    public function view(User $user, EmployeeMonthTask $task): bool
    {
        if ($user->isAdminLike()) {
            return true;
        }

        return $this->isDepartmentManagerTaskOwner($user, $task);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, EmployeeMonthTask $task): bool
    {
        if ($user->isAdminLike()) {
            return true;
        }

        return $this->isDepartmentManagerTaskOwner($user, $task);
    }

    public function delete(User $user, EmployeeMonthTask $task): bool
    {
        if ($user->isAdminLike()) {
            return true;
        }

        return $this->isDepartmentManagerTaskOwner($user, $task);
    }

    private function isDepartmentManagerTaskOwner(User $user, EmployeeMonthTask $task): bool
    {
        if (! $user->isDepartmentManager()) {
            return false;
        }

        $actorDepartmentId = $user->employee?->department_id;

        if ($actorDepartmentId === null) {
            return false;
        }

        $employeesQuery = $task->employees();

        if (! $employeesQuery->exists()) {
            return false;
        }

        return ! $employeesQuery
            ->where('employees.department_id', '!=', (int) $actorDepartmentId)
            ->exists();
    }
}
