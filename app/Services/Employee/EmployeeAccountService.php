<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Notifications\EmailNotificationService;
use InvalidArgumentException;

class EmployeeAccountService
{
    public function __construct(
        private readonly EmailNotificationService $emailNotificationService,
    ) {}

    public function provisionForEmployee(Employee $employee, ?string $email = null, bool $allowEmptyEmail = true): User
    {
        $account = $employee->user;
        $oldEmail = $account?->email;
        $normalizedEmail = $this->normalizeEmail($email);

        if (! $account && ! $allowEmptyEmail && $normalizedEmail === null) {
            throw new InvalidArgumentException('An email is required when provisioning a new employee account.');
        }

        $payload = [
            'name' => $employee->name,
            'role' => $this->resolveRole($employee),
            'employee_id' => $employee->id,
        ];

        if ($normalizedEmail !== null) {
            $payload['email'] = $normalizedEmail;
        } elseif (! $account && $allowEmptyEmail) {
            $payload['email'] = null;
        }

        if (! $account) {
            $payload['password'] = (string) config('attendance.employee_accounts.initial_password', '123456789');
            $payload['must_change_password'] = true;

            $account = User::create($payload);
        } else {
            $account->update($payload);
        }

        UserProfile::firstOrCreate(['user_id' => $account->id]);

        $this->emailNotificationService->sendWelcomeOnFirstEmail($account, $oldEmail);

        return $account->fresh();
    }

    private function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = strtolower(trim($email));

        return $normalized === '' ? null : $normalized;
    }

    private function resolveRole(Employee $employee): string
    {
        if ($employee->is_department_manager) {
            return 'department_manager';
        }

        $employee->loadMissing('jobTitleRef');

        $mapped = $employee->jobTitleRef?->system_role_mapping;
        if (is_string($mapped) && $mapped !== '') {
            return $mapped;
        }

        if ($employee->relationLoaded('jobTitleRef')) {
            $legacyKey = $employee->jobTitleRef?->key;
            if (is_string($legacyKey) && $legacyKey !== '') {
                return match ($legacyKey) {
                    'admin' => 'admin',
                    'manager' => 'manager',
                    'hr' => 'hr',
                    'evaluator' => 'user',
                    'office_girl' => 'office_girl',
                    default => 'employee',
                };
            }
        }

        return 'employee';
    }
}
