<?php

namespace App\Services\Department;

use App\Models\Department;
use App\Models\DepartmentEmployeeHistory;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DepartmentService
{
    public function create(array $data): Department
    {
        return DB::transaction(function () use ($data) {
            $department = Department::query()->create([
                'name' => (string) $data['name'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            if (! empty($data['manager_employee_id'])) {
                $this->assignManager(
                    $department,
                    (int) $data['manager_employee_id'],
                    $data['assigned_by_user_id'] ?? null,
                );
            }

            if (! empty($data['employee_ids']) && is_array($data['employee_ids'])) {
                $this->assignEmployees(
                    $department,
                    array_map('intval', $data['employee_ids']),
                    $data['assigned_by_user_id'] ?? null,
                );
            }

            return $department->fresh(['managerEmployee', 'employees']);
        });
    }

    public function update(Department $department, array $data): Department
    {
        return DB::transaction(function () use ($department, $data) {
            $department->update([
                'name' => (string) ($data['name'] ?? $department->name),
                'is_active' => (bool) ($data['is_active'] ?? $department->is_active),
            ]);

            if (array_key_exists('manager_employee_id', $data)) {
                $this->assignManager(
                    $department,
                    $data['manager_employee_id'] !== null ? (int) $data['manager_employee_id'] : null,
                    $data['assigned_by_user_id'] ?? null,
                );
            }

            if (array_key_exists('employee_ids', $data) && is_array($data['employee_ids'])) {
                $this->assignEmployees(
                    $department,
                    array_map('intval', $data['employee_ids']),
                    $data['assigned_by_user_id'] ?? null,
                );
            }

            return $department->fresh(['managerEmployee', 'employees']);
        });
    }

    public function delete(Department $department): void
    {
        DB::transaction(function () use ($department): void {
            $this->assignManager($department, null, null);

            Employee::query()
                ->where('department_id', $department->id)
                ->update(['department_id' => null]);

            DepartmentEmployeeHistory::query()
                ->where('department_id', $department->id)
                ->whereNull('to_date')
                ->update(['to_date' => now()->toDateString()]);

            $department->delete();
        });
    }

    public function assignManager(Department $department, ?int $managerEmployeeId, ?int $assignedByUserId): Department
    {
        return DB::transaction(function () use ($department, $managerEmployeeId, $assignedByUserId) {
            $previousManagerId = $department->manager_employee_id;

            if ($previousManagerId !== null && ($managerEmployeeId === null || $managerEmployeeId !== (int) $previousManagerId)) {
                $previous = Employee::query()->with('user')->find($previousManagerId);

                if ($previous) {
                    $previous->update(['is_department_manager' => false]);

                    if ($previous->user && $previous->user->role === 'department_manager') {
                        $previous->user->update(['role' => 'employee']);
                    }
                }
            }

            if ($managerEmployeeId === null) {
                $department->update(['manager_employee_id' => null]);

                return $department->fresh(['managerEmployee', 'employees']);
            }

            $manager = Employee::query()->with('user')->find($managerEmployeeId);

            if (! $manager) {
                throw new RuntimeException('مدير القسم المحدد غير موجود.');
            }

            if ((int) $manager->department_id !== (int) $department->id) {
                $this->attachEmployeeToDepartment($manager, $department, $assignedByUserId);
            }

            $manager->update(['is_department_manager' => true]);

            if ($manager->user && ! in_array($manager->user->role, ['admin', 'hr', 'manager'], true)) {
                $manager->user->update(['role' => 'department_manager']);
            }

            $department->update(['manager_employee_id' => $manager->id]);

            return $department->fresh(['managerEmployee', 'employees']);
        });
    }

    public function assignEmployees(Department $department, array $employeeIds, ?int $assignedByUserId): Department
    {
        return DB::transaction(function () use ($department, $employeeIds, $assignedByUserId) {
            $validIds = collect($employeeIds)
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if (empty($validIds)) {
                return $department->fresh(['managerEmployee', 'employees']);
            }

            $employees = Employee::query()
                ->whereIn('id', $validIds)
                ->get();

            foreach ($employees as $employee) {
                if ((int) $employee->department_id === (int) $department->id) {
                    continue;
                }

                $this->attachEmployeeToDepartment($employee, $department, $assignedByUserId);
            }

            return $department->fresh(['managerEmployee', 'employees']);
        });
    }

    private function attachEmployeeToDepartment(Employee $employee, Department $department, ?int $assignedByUserId): void
    {
        if ($employee->department_id !== null) {
            DepartmentEmployeeHistory::query()
                ->where('employee_id', $employee->id)
                ->where('department_id', $employee->department_id)
                ->whereNull('to_date')
                ->update(['to_date' => now()->toDateString()]);
        }

        $employee->update(['department_id' => $department->id]);

        DepartmentEmployeeHistory::query()->create([
            'employee_id' => $employee->id,
            'department_id' => $department->id,
            'from_date' => now()->toDateString(),
            'to_date' => null,
            'assigned_by_user_id' => $assignedByUserId,
        ]);
    }
}
