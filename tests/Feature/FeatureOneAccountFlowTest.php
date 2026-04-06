<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\Employee\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertStringEndsWith('@perfection.com', $user->email);
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

    public function test_excel_import_employee_gets_auto_generated_account(): void
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

        $this->assertStringEndsWith('@perfection.com', $user->email);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
        ]);
    }
}
