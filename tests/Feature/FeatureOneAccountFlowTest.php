<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Notifications\WelcomeEmployeeNotification;
use App\Services\Employee\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FeatureOneAccountFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_employee_creation_auto_provisions_account_and_profile(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('employees.store'), [
                'ac_no' => 'AC-2001',
                'name' => 'Omar Ali',
                'account_email' => 'omar.ali@example.com',
                'job_title' => 'developer',
                'basic_salary' => 7000,
                'is_remote_worker' => 0,
            ])
            ->assertRedirect();

        $employee = Employee::where('ac_no', 'AC-2001')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'employee_id' => $employee->id,
            'role' => 'employee',
            'must_change_password' => true,
        ]);

        $user = User::where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame('omar.ali@example.com', $user->email);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
        ]);
    }

    public function test_job_title_maps_to_expected_role(): void
    {
        $admin = $this->makeAdmin();

        $cases = [
            ['ac' => 'AC-3001', 'name' => 'Manager User', 'job' => 'manager', 'role' => 'manager'],
            ['ac' => 'AC-3002', 'name' => 'HR User', 'job' => 'hr', 'role' => 'hr'],
            ['ac' => 'AC-3003', 'name' => 'Admin User', 'job' => 'admin', 'role' => 'admin'],
        ];

        foreach ($cases as $case) {
            $this->actingAs($admin)
                ->post(route('employees.store'), [
                    'ac_no' => $case['ac'],
                    'name' => $case['name'],
                    'account_email' => strtolower(str_replace(' ', '.', $case['name'])) . '@example.com',
                    'job_title' => $case['job'],
                    'basic_salary' => 8000,
                    'is_remote_worker' => 0,
                ])
                ->assertRedirect();

            $employee = Employee::where('ac_no', $case['ac'])->firstOrFail();
            $this->assertDatabaseHas('users', [
                'employee_id' => $employee->id,
                'role' => $case['role'],
            ]);
        }
    }

    public function test_first_login_forces_password_change(): void
    {
        $employee = Employee::factory()->create([
            'name' => 'First Login User',
            'job_title' => 'developer',
        ]);

        $user = User::factory()->create([
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.force-change'));

        $this->actingAs($user)
            ->put(route('password.force-change.update'), [
                'password' => 'NewStrongPass123!',
                'password_confirmation' => 'NewStrongPass123!',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertNotNull($user->fresh()->last_password_changed_at);
    }

    public function test_manager_and_hr_have_admin_like_route_access(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'must_change_password' => false]);
        $hr = User::factory()->create(['role' => 'hr', 'must_change_password' => false]);

        $this->actingAs($manager)
            ->get(route('employees.create'))
            ->assertOk();

        $this->actingAs($hr)
            ->get(route('settings.index'))
            ->assertOk();
    }

    public function test_updating_employee_email_switches_login_to_the_new_email_only(): void
    {
        $admin = $this->makeAdmin();

        $employee = Employee::factory()->create([
            'ac_no' => 'AC-CHANGE-1',
            'name' => 'Email Switch User',
            'basic_salary' => 6500,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'employee',
            'employee_id' => $employee->id,
            'email' => 'old.login@example.com',
            'password' => 'password',
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('employees.update', $employee), [
                'ac_no' => $employee->ac_no,
                'name' => $employee->name,
                'account_email' => 'new.login@example.com',
                'job_title' => 'developer',
                'basic_salary' => 6500,
                'is_remote_worker' => 0,
            ])
            ->assertRedirect(route('employees.show', $employee));

        $this->assertSame('new.login@example.com', $user->fresh()->email);

        auth()->logout();

        $this->post(route('login'), [
            'email' => 'old.login@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->post(route('login'), [
            'email' => 'new.login@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_excel_import_employee_gets_account_without_email_until_admin_adds_one(): void
    {
        /** @var EmployeeService $service */
        $service = app(EmployeeService::class);

        $employee = $service->findOrCreateFromExcel('AC-EXCEL-1', 'Excel User');

        $this->assertDatabaseHas('users', [
            'employee_id' => $employee->id,
            'must_change_password' => true,
            'role' => 'employee',
        ]);

        $user = User::where('employee_id', $employee->id)->firstOrFail();

        $this->assertNull($user->email);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
        ]);
    }

    public function test_excel_import_keeps_existing_system_name_when_excel_name_changes(): void
    {
        /** @var EmployeeService $service */
        $service = app(EmployeeService::class);

        $employee = Employee::factory()->create([
            'ac_no' => 'AC-EXCEL-2',
            'name' => 'System Name',
        ]);

        $resolved = $service->findOrCreateFromExcel('AC-EXCEL-2', 'Excel Different Name');

        $this->assertSame($employee->id, $resolved->id);
        $this->assertSame('System Name', $resolved->fresh()->name);
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_first_real_email_added_to_imported_employee_sends_welcome_notification(): void
    {
        Notification::fake();

        $admin = $this->makeAdmin();

        /** @var EmployeeService $service */
        $service = app(EmployeeService::class);
        $employee = $service->findOrCreateFromExcel('AC-EXCEL-3', 'Imported Welcome User');

        $account = User::where('employee_id', $employee->id)->firstOrFail();
        $this->assertNull($account->email);

        $this->actingAs($admin)
            ->put(route('employees.update', $employee), [
                'ac_no' => $employee->ac_no,
                'name' => $employee->name,
                'account_email' => 'imported.user@example.com',
                'job_title' => 'developer',
                'basic_salary' => 5000,
                'is_remote_worker' => 0,
            ])
            ->assertRedirect(route('employees.show', $employee));

        Notification::assertSentTo(
            $account->fresh(),
            WelcomeEmployeeNotification::class
        );
    }
}
