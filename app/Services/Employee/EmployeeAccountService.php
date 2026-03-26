<?php

namespace App\Services\Employee;

use App\Enums\JobTitle;
use App\Models\Employee;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Str;

class EmployeeAccountService
{
    public function provisionForEmployee(Employee $employee): User
    {
        $account = $employee->user;
        $email = $this->generateUniqueEmail(
            employeeName: $employee->name,
            employeeAcNo: $employee->ac_no,
            ignoreUserId: $account?->id,
        );

        $payload = [
            'name' => $employee->name,
            'email' => $email,
            'role' => $this->resolveRole($employee),
            'employee_id' => $employee->id,
        ];

        if (! $account) {
            $payload['password'] = (string) config('attendance.employee_accounts.initial_password', '123456789');
            $payload['must_change_password'] = true;

            $account = User::create($payload);
        } else {
            $account->update($payload);
        }

        UserProfile::firstOrCreate(['user_id' => $account->id]);

        return $account->fresh();
    }

    private function resolveRole(Employee $employee): string
    {
        if ($employee->job_title instanceof JobTitle) {
            return $employee->job_title->systemRole();
        }

        return 'employee';
    }

    private function generateUniqueEmail(string $employeeName, string $employeeAcNo, ?int $ignoreUserId = null): string
    {
        $domain = trim((string) config('attendance.employee_accounts.email_domain', 'perfection.com'));
        $baseSlug = Str::slug($employeeName);

        if ($baseSlug === '') {
            $baseSlug = 'employee-' . Str::slug($employeeAcNo);
        }

        if ($baseSlug === '' || $baseSlug === 'employee-') {
            $baseSlug = 'employee-' . $employeeAcNo;
        }

        $baseSlug = preg_replace('/[^a-z0-9\-]/', '', Str::lower($baseSlug)) ?: 'employee-' . $employeeAcNo;

        $attempt = 0;
        do {
            $suffix = $attempt === 0 ? '' : (string) ($attempt + 1);
            $email = $baseSlug . $suffix . '@' . $domain;

            $query = User::where('email', $email);
            if ($ignoreUserId !== null) {
                $query->where('id', '!=', $ignoreUserId);
            }

            $exists = $query->exists();
            $attempt++;
        } while ($exists);

        return $email;
    }
}
