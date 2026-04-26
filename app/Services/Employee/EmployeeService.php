<?php

namespace App\Services\Employee;

use App\Enums\JobTitle as LegacyJobTitle;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\Department\DepartmentScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EmployeeService
{
    public function __construct(
        private readonly EmployeeAccountService $accountService,
        private readonly DepartmentScopeService $departmentScopeService,
    ) {}

    /**
     * Ø¬Ù„Ø¨ Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„Ù…ÙˆØ¸ÙÙŠÙ† Ù…Ø¹ Ø¨Ø­Ø« ÙˆÙÙ„ØªØ±Ø© ÙˆØªÙ‚Ø³ÙŠÙ… ØµÙØ­Ø§Øª
     */
    public function getEmployees(array $filters = [], int $perPage = 20, ?User $actor = null, bool $applyVisibilityScope = true): LengthAwarePaginator
    {
        $query = $this->buildEmployeesQuery($actor, $applyVisibilityScope);
        $this->applyEmployeeFilters($query, $filters);

        return $query->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function getEmployeeDirectorySummary(?User $actor = null, bool $applyVisibilityScope = true): array
    {
        $baseQuery = $this->buildEmployeesQuery($actor, $applyVisibilityScope);

        $totalEmployees = (clone $baseQuery)->count();
        $readyAccounts = (clone $baseQuery)
            ->whereHas('user', fn (Builder $query) => $query->whereNotNull('email')->where('email', '<>', ''))
            ->count();
        $missingEmail = (clone $baseQuery)
            ->whereHas('user', fn (Builder $query) => $query->where(function (Builder $emailQuery): void {
                $emailQuery->whereNull('email')->orWhere('email', '');
            }))
            ->count();
        $withoutAccount = (clone $baseQuery)
            ->whereDoesntHave('user')
            ->count();

        return [
            'total_employees' => $totalEmployees,
            'ready_accounts' => $readyAccounts,
            'missing_email' => $missingEmail,
            'without_account' => $withoutAccount,
            'pending_login_setup' => $missingEmail + $withoutAccount,
        ];
    }

    /**
     * Ø¥Ù†Ø´Ø§Ø¡ Ù…ÙˆØ¸Ù Ø¬Ø¯ÙŠØ¯
     */
    public function create(array $data): Employee
    {
        $jobTitle = $this->resolveJobTitleFromPayload($data);
        $accountEmail = $this->extractAccountEmail($data);

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

        $this->accountService->provisionForEmployee($employee, $accountEmail, allowEmptyEmail: false);

        if (! empty($data['employment_start_date'])) {
            $employee->leaveProfile()->updateOrCreate(
                ['employee_id' => (int) $employee->id],
                ['employment_start_date' => $data['employment_start_date']]
            );
        }

        return $employee->fresh();
    }

    /**
     * ØªØ­Ø¯ÙŠØ« Ø¨ÙŠØ§Ù†Ø§Øª Ù…ÙˆØ¸Ù
     */
    public function update(Employee $employee, array $data): Employee
    {
        $jobTitle = $this->resolveJobTitleFromPayload($data);
        $accountEmail = $this->extractAccountEmail($data);

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

        $this->accountService->provisionForEmployee($employee, $accountEmail, allowEmptyEmail: true);

        return $employee->fresh();
    }

    /**
     * Ø­Ø°Ù Ù†Ù‡Ø§Ø¦ÙŠ Ù„Ù„Ù…ÙˆØ¸Ù (Hard Delete)
     */
    public function deletePermanently(Employee $employee): void
    {
        $employee->forceDelete();
    }

    /**
     * ØªÙØ¹ÙŠÙ„ Ù…ÙˆØ¸Ù
     */
    public function activate(Employee $employee): void
    {
        $employee->restore();
        $employee->update(['is_active' => true]);
    }

    /**
     * Ø¥Ù†Ø´Ø§Ø¡ Ø£Ùˆ ØªØ­Ø¯ÙŠØ« Ù…ÙˆØ¸Ù Ù…Ù† Ø¨ÙŠØ§Ù†Ø§Øª Excel (ÙŠÙØ³ØªØ®Ø¯Ù… Ø£Ø«Ù†Ø§Ø¡ Ø§Ù„Ø§Ø³ØªÙŠØ±Ø§Ø¯)
     */
    public function findOrCreateFromExcel(string $acNo, string $name): Employee
    {
        $employee = Employee::withTrashed()->where('ac_no', $acNo)->first();

        if ($employee) {
            // ØªØ«Ø¨ÙŠØª Ø§Ø³Ù… Ø§Ù„Ù†Ø¸Ø§Ù… ÙˆØ¹Ø¯Ù… ØªØ­Ø¯ÙŠØ«Ù‡ Ù…Ù† Excel.
            if ($employee->name !== $name) {
                Log::info('Excel import name mismatch ignored for existing employee.', [
                    'employee_id' => $employee->id,
                    'ac_no' => $acNo,
                    'system_name' => $employee->name,
                    'excel_name' => $name,
                ]);
            }

            // Ø§Ø³ØªØ¹Ø§Ø¯Ø© Ø¥Ù† ÙƒØ§Ù† Ù…Ø­Ø°ÙˆÙØ§Ù‹
            if ($employee->trashed()) {
                $employee->restore();
                $employee->update(['is_active' => true]);
            }

            // Ø¶Ù…Ø§Ù† ÙˆØ¬ÙˆØ¯ Ø­Ø³Ø§Ø¨ Ù…Ø³ØªØ®Ø¯Ù… Ù„Ù„Ù…ÙˆØ¸Ù Ø£Ø«Ù†Ø§Ø¡ Ø§Ù„Ø§Ø³ØªÙŠØ±Ø§Ø¯.
            $this->accountService->provisionForEmployee($employee, allowEmptyEmail: true);

            return $employee;
        }

        $employee = Employee::create([
            'ac_no'        => $acNo,
            'name'         => $name,
            'basic_salary' => 0,
            'is_active'    => true,
        ]);

        // Ø¥Ù†Ø´Ø§Ø¡ Ø­Ø³Ø§Ø¨ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… ØªÙ„Ù‚Ø§Ø¦ÙŠØ§Ù‹ Ù„Ù„Ù…ÙˆØ¸Ù Ø§Ù„Ø¬Ø¯ÙŠØ¯ Ù…Ù† Ø§Ù„Ø¥ÙƒØ³ÙŠÙ„.
        $this->accountService->provisionForEmployee($employee, allowEmptyEmail: true);

        return $employee;
    }

    /**
     * Ù‚Ø§Ø¦Ù…Ø© Ø¨Ø³ÙŠØ·Ø© Ù„Ø§Ø³ØªØ®Ø¯Ø§Ù…Ù‡Ø§ ÙÙŠ Ø§Ù„Ù€ Selects
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

    private function extractAccountEmail(array $data): ?string
    {
        if (! array_key_exists('account_email', $data)) {
            return null;
        }

        $email = trim((string) $data['account_email']);

        return $email === '' ? null : $email;
    }

    private function buildEmployeesQuery(?User $actor = null, bool $applyVisibilityScope = true): Builder
    {
        $query = Employee::query()
            ->with(['user.profile', 'department:id,name', 'jobTitleRef:id,name_ar', 'leaveProfile:employee_id,employment_start_date']);

        if ($actor !== null && $applyVisibilityScope && ! $actor->isEvaluatorUser()) {
            $this->departmentScopeService->applyEmployeeScope($query, $actor);
        }

        return $query;
    }

    private function applyEmployeeFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $employeeQuery) use ($search): void {
                $employeeQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('ac_no', 'like', "%{$search}%");
            });
        }

        $emailStatus = (string) ($filters['email_status'] ?? 'all');

        if ($emailStatus === 'missing') {
            $query->where(function (Builder $employeeQuery): void {
                $employeeQuery->whereDoesntHave('user')
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->whereNull('email')->orWhere('email', ''));
            });
        }

        if ($emailStatus === 'ready') {
            $query->whereHas('user', fn (Builder $userQuery) => $userQuery->whereNotNull('email')->where('email', '<>', ''));
        }

        if ($emailStatus === 'no_account') {
            $query->whereDoesntHave('user');
        }
    }
}
