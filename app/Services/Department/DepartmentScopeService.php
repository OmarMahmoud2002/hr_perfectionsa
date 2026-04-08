<?php

namespace App\Services\Department;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DepartmentScopeService
{
    public function applyEmployeeScope(Builder $query, User $actor, string $employeeTable = 'employees'): Builder
    {
        if ($actor->isAdminLike()) {
            return $query;
        }

        if ($actor->isDepartmentManager()) {
            $departmentId = $actor->employee?->department_id;

            if ($departmentId === null) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where("{$employeeTable}.department_id", $departmentId);
        }

        if ($actor->employee_id !== null) {
            return $query->where("{$employeeTable}.id", (int) $actor->employee_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function visibleEmployeeIdsQuery(User $actor)
    {
        $query = Employee::query()->select('id');

        $this->applyEmployeeScope($query, $actor);

        return $query;
    }

    public function canAccessEmployee(User $actor, Employee $employee): bool
    {
        if ($actor->isAdminLike()) {
            return true;
        }

        if ($actor->isDepartmentManager()) {
            $departmentId = $actor->employee?->department_id;

            return $departmentId !== null && (int) $employee->department_id === (int) $departmentId;
        }

        if ($actor->employee_id !== null) {
            return (int) $actor->employee_id === (int) $employee->id;
        }

        return false;
    }
}
