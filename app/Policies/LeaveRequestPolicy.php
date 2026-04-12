<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'hr'], true)
            || $user->isDepartmentManager()
            || $user->employee_id !== null;
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        if (in_array($user->role, ['admin', 'manager', 'hr'], true)) {
            return true;
        }

        if ($user->isDepartmentManager()) {
            return (int) ($user->employee_id ?? 0) === (int) ($leaveRequest->manager_employee_id ?? 0)
                || (int) ($user->employee_id ?? 0) === (int) $leaveRequest->employee_id;
        }

        return (int) ($user->employee_id ?? 0) === (int) $leaveRequest->employee_id;
    }

    public function create(User $user): bool
    {
        return $user->employee_id !== null;
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($leaveRequest->finalized_at !== null || in_array($leaveRequest->status, ['approved', 'rejected'], true)) {
            return false;
        }

        $leaveRequest->loadMissing('employee.user');
        $requesterRole = (string) ($leaveRequest->employee?->user?->role ?? '');
        $isHrLikeRequester = in_array($requesterRole, ['hr', 'admin'], true);

        if (in_array($user->role, ['hr', 'admin'], true)) {
            return ! $isHrLikeRequester && $leaveRequest->hr_status === 'pending';
        }

        if ($user->role === 'department_manager') {
            return ! $isHrLikeRequester
                && $leaveRequest->manager_status === 'pending'
                && (int) ($user->employee_id ?? 0) === (int) ($leaveRequest->manager_employee_id ?? 0);
        }

        if ($user->role === 'manager') {
            return $isHrLikeRequester && $leaveRequest->manager_status === 'pending';
        }

        return false;
    }

    public function partialApprove(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }
}
