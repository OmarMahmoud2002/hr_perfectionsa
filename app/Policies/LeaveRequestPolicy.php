<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminLike()
            || $user->isDepartmentManager()
            || $user->employee_id !== null;
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->isAdminLike()) {
            return true;
        }

        if ($user->isDepartmentManager()) {
            return (int) ($user->employee_id ?? 0) === (int) ($leaveRequest->manager_employee_id ?? 0);
        }

        return (int) ($user->employee_id ?? 0) === (int) $leaveRequest->employee_id;
    }

    public function create(User $user): bool
    {
        return $user->employee_id !== null;
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->isAdminLike()) {
            return true;
        }

        return $user->isDepartmentManager()
            && (int) ($user->employee_id ?? 0) === (int) ($leaveRequest->manager_employee_id ?? 0)
            && $leaveRequest->manager_status !== 'not_required';
    }

    public function partialApprove(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->isAdminLike();
    }
}
