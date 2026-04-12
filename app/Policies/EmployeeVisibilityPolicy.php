<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeeVisibilityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminLike()
            || $user->isDepartmentManager()
            || $user->isWorkforceMember()
            || $user->isEvaluatorUser();
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($user->isAdminLike()) {
            return true;
        }

        if (! $user->isDepartmentManager()) {
            return false;
        }

        $actorDepartmentId = $user->employee?->department_id;

        return $actorDepartmentId !== null
            && (int) $actorDepartmentId === (int) $employee->department_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->isAdminLike();
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->isAdminLike();
    }
}
