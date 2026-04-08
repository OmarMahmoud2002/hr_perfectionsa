<?php

namespace App\Policies;

use App\Models\DailyPerformanceEntry;
use App\Models\User;

class DailyPerformanceReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'hr', 'user', 'department_manager'], true);
    }

    public function review(User $user, DailyPerformanceEntry $entry): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if (! $user->isDepartmentManager()) {
            return true;
        }

        $actorDepartmentId = $user->employee?->department_id;

        return $actorDepartmentId !== null
            && (int) $actorDepartmentId === (int) $entry->employee?->department_id;
    }
}
