<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EmployeeService
{
    public function __construct(
        private readonly EmployeeAccountService $accountService,
    ) {}

    /**
     * جلب قائمة الموظفين مع بحث وفلترة وتقسيم صفحات
     */
    public function getEmployees(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::query()->with('user.profile');

        // البحث بالاسم أو الرقم
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ac_no', 'like', "%{$search}%");
            });
        }

        // فلترة بالحالة
        if (isset($filters['status']) && $filters['status'] !== '') {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        return $query->orderBy('name')->paginate($perPage)->withQueryString();
    }

    /**
     * إنشاء موظف جديد
     */
    public function create(array $data): Employee
    {
        $employee = Employee::create([
            'ac_no'               => $data['ac_no'],
            'name'                => $data['name'],
            'job_title'           => $data['job_title'] ?? null,
            'basic_salary'        => $data['basic_salary'] ?? 0,
            'is_active'           => true,
            'work_start_time'     => $data['work_start_time'] ?? null,
            'work_end_time'       => $data['work_end_time'] ?? null,
            'overtime_start_time' => $data['overtime_start_time'] ?? null,
            'late_grace_minutes'  => isset($data['late_grace_minutes']) && $data['late_grace_minutes'] !== '' ? (int) $data['late_grace_minutes'] : null,
        ]);

        $this->accountService->provisionForEmployee($employee);

        return $employee->fresh();
    }

    /**
     * تحديث بيانات موظف
     */
    public function update(Employee $employee, array $data): Employee
    {
        $employee->update([
            'ac_no'               => $data['ac_no'],
            'name'                => $data['name'],
            'job_title'           => $data['job_title'] ?? null,
            'basic_salary'        => $data['basic_salary'] ?? 0,
            'work_start_time'     => $data['work_start_time'] ?? null,
            'work_end_time'       => $data['work_end_time'] ?? null,
            'overtime_start_time' => $data['overtime_start_time'] ?? null,
            'late_grace_minutes'  => isset($data['late_grace_minutes']) && $data['late_grace_minutes'] !== '' ? (int) $data['late_grace_minutes'] : null,
        ]);

        $this->accountService->provisionForEmployee($employee);

        return $employee->fresh();
    }

    /**
     * تعطيل موظف (Soft Delete)
     */
    public function deactivate(Employee $employee): void
    {
        $employee->update(['is_active' => false]);
        $employee->delete();
    }

    /**
     * تفعيل موظف
     */
    public function activate(Employee $employee): void
    {
        $employee->restore();
        $employee->update(['is_active' => true]);
    }

    /**
     * إنشاء أو تحديث موظف من بيانات Excel (يُستخدم أثناء الاستيراد)
     */
    public function findOrCreateFromExcel(string $acNo, string $name): Employee
    {
        $employee = Employee::withTrashed()->where('ac_no', $acNo)->first();

        if ($employee) {
            // تحديث الاسم إن اختلف
            if ($employee->name !== $name) {
                $employee->name = $name;
                $employee->save();
            }
            // استعادة إن كان محذوفاً
            if ($employee->trashed()) {
                $employee->restore();
                $employee->update(['is_active' => true]);
            }

            // ضمان وجود حساب مستخدم للموظف أثناء الاستيراد.
            $this->accountService->provisionForEmployee($employee);

            return $employee;
        }

        $employee = Employee::create([
            'ac_no'        => $acNo,
            'name'         => $name,
            'basic_salary' => 0,
            'is_active'    => true,
        ]);

        // إنشاء حساب المستخدم تلقائياً للموظف الجديد من الإكسيل.
        $this->accountService->provisionForEmployee($employee);

        return $employee;
    }

    /**
     * قائمة بسيطة لاستخدامها في الـ Selects
     */
    public function getForSelect(): Collection
    {
        return Employee::active()->orderBy('name')->get(['id', 'ac_no', 'name']);
    }
}
