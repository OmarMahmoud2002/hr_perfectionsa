<?php

namespace App\Services\Employee;

use App\Enums\JobTitle as LegacyJobTitle;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\Department\DepartmentScopeService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EmployeeService
{
    public function __construct(
        private readonly EmployeeAccountService $accountService,
        private readonly DepartmentScopeService $departmentScopeService,
    ) {}

    /**
     * جلب قائمة الموظفين مع بحث وفلترة وتقسيم صفحات
     */
    public function getEmployees(array $filters = [], int $perPage = 15, ?User $actor = null, bool $applyVisibilityScope = true): LengthAwarePaginator
    {
        $query = Employee::query()->with(['user.profile', 'department:id,name', 'jobTitleRef:id,name_ar', 'leaveProfile:employee_id,employment_start_date']);

        if ($actor !== null && $applyVisibilityScope && ! $actor->isEvaluatorUser()) {
            $this->departmentScopeService->applyEmployeeScope($query, $actor);
        }

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
        $jobTitle = $this->resolveJobTitleFromPayload($data);

        $legacyJobTitle = $this->resolveLegacyJobTitleKey($jobTitle);

        $employee = Employee::create([
            'ac_no'               => $data['ac_no'],
            'name'                => $data['name'],
            'job_title'           => $legacyJobTitle,
            'job_title_id'        => $jobTitle?->id,
            'department_id'       => $data['department_id'] ?? null,
            'basic_salary'        => $data['basic_salary'] ?? 0,
            'is_active'           => true,
            'is_remote_worker'    => (bool) ($data['is_remote_worker'] ?? false),
            'allow_remote_from_anywhere' => (bool) ($data['allow_remote_from_anywhere'] ?? false),
            'work_start_time'     => $data['work_start_time'] ?? null,
            'work_end_time'       => $data['work_end_time'] ?? null,
            'overtime_start_time' => $data['overtime_start_time'] ?? null,
            'late_grace_minutes'  => isset($data['late_grace_minutes']) && $data['late_grace_minutes'] !== '' ? (int) $data['late_grace_minutes'] : null,
        ]);

        $this->accountService->provisionForEmployee($employee);

        if (! empty($data['employment_start_date'])) {
            $employee->leaveProfile()->updateOrCreate(
                ['employee_id' => (int) $employee->id],
                ['employment_start_date' => $data['employment_start_date']]
            );
        }

        return $employee->fresh();
    }

    /**
     * تحديث بيانات موظف
     */
    public function update(Employee $employee, array $data): Employee
    {
        $jobTitle = $this->resolveJobTitleFromPayload($data);

        $legacyJobTitle = $this->resolveLegacyJobTitleKey($jobTitle);

        $employee->update([
            'ac_no'               => $data['ac_no'],
            'name'                => $data['name'],
            'job_title'           => $legacyJobTitle,
            'job_title_id'        => $jobTitle?->id,
            'department_id'       => $data['department_id'] ?? null,
            'basic_salary'        => $data['basic_salary'] ?? 0,
            'is_remote_worker'    => (bool) ($data['is_remote_worker'] ?? false),
            'allow_remote_from_anywhere' => (bool) ($data['allow_remote_from_anywhere'] ?? false),
            'work_start_time'     => $data['work_start_time'] ?? null,
            'work_end_time'       => $data['work_end_time'] ?? null,
            'overtime_start_time' => $data['overtime_start_time'] ?? null,
            'late_grace_minutes'  => isset($data['late_grace_minutes']) && $data['late_grace_minutes'] !== '' ? (int) $data['late_grace_minutes'] : null,
        ]);

        if (array_key_exists('employment_start_date', $data)) {
            $employee->leaveProfile()->updateOrCreate(
                ['employee_id' => (int) $employee->id],
                ['employment_start_date' => $data['employment_start_date'] ?: null]
            );
        }

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

    private function resolveLegacyJobTitleKey(?JobTitle $jobTitle): ?string
    {
        if (! $jobTitle || ! is_string($jobTitle->key) || $jobTitle->key === '') {
            return null;
        }

        return LegacyJobTitle::tryFrom($jobTitle->key)?->value;
    }

    private function resolveJobTitleFromPayload(array $data): ?JobTitle
    {
        if (! empty($data['job_title_id'])) {
            return JobTitle::query()->find((int) $data['job_title_id']);
        }

        if (! empty($data['job_title'])) {
            return JobTitle::query()->where('key', (string) $data['job_title'])->first();
        }

        return null;
    }
}
