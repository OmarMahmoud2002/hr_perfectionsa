<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeEmployee(): User
    {
        return User::factory()->create(['role' => 'employee']);
    }

    public function test_admin_can_create_employee(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('employees.store'), [
            'ac_no'        => 'AC-1001',
            'name'         => 'موظف تجريبي',
            'job_title'    => 'developer',
            'basic_salary' => 5000,
            'is_remote_worker' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('employees', [
            'ac_no'     => 'AC-1001',
            'name'      => 'موظف تجريبي',
            'is_active' => true,
        ]);
    }

    public function test_viewer_cannot_access_employee_creation(): void
    {
        $viewer = $this->makeEmployee();

        $this->actingAs($viewer)
            ->get(route('employees.create'))
            ->assertStatus(403);

        $this->actingAs($viewer)
            ->post(route('employees.store'), [
                'ac_no'        => 'AC-999',
                'name'         => 'Should Fail',
                'job_title'    => 'developer',
                'basic_salary' => 1000,
                'is_remote_worker' => 0,
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_deactivate_employee(): void
    {
        $admin    = $this->makeAdmin();
        $employee = Employee::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)
            ->delete(route('employees.destroy', $employee));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success');

        $deleted = Employee::withTrashed()->find($employee->id);
        $this->assertFalse($deleted->is_active);
        $this->assertNotNull($deleted->deleted_at, 'Employee should be soft-deleted');
    }

    public function test_admin_can_create_remote_employee_with_scheduled_days(): void
    {
        $admin = $this->makeAdmin();

        $location = Location::query()->create([
            'name' => 'HQ',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'radius' => 300,
        ]);

        $response = $this->actingAs($admin)->post(route('employees.store'), [
            'ac_no' => 'AC-2001',
            'name' => 'موظف ريموت',
            'job_title' => 'developer',
            'basic_salary' => 6500,
            'is_remote_worker' => 1,
            'location_ids' => [$location->id],
            'remote_allowed_dates' => ['2026-04-10', '2026-04-12'],
        ]);

        $response->assertRedirect();
        $employee = Employee::query()->where('ac_no', 'AC-2001')->firstOrFail();

        $this->assertDatabaseHas('employee_remote_work_days', [
            'employee_id' => $employee->id,
            'work_date' => '2026-04-10',
        ]);

        $this->assertDatabaseHas('employee_remote_work_days', [
            'employee_id' => $employee->id,
            'work_date' => '2026-04-12',
        ]);
    }

    public function test_switching_employee_to_non_remote_clears_scheduled_remote_days(): void
    {
        $admin = $this->makeAdmin();
        $jobTitle = JobTitle::query()->create([
            'name_ar' => 'مطور اختبار',
            'key' => 'test_developer',
            'system_role_mapping' => 'employee',
            'is_active' => true,
            'is_system' => false,
        ]);

        $employee = Employee::factory()->create([
            'is_remote_worker' => true,
            'job_title_id' => $jobTitle->id,
        ]);

        $employee->remoteWorkDays()->create(['work_date' => '2026-04-14']);
        $employee->remoteWorkDays()->create(['work_date' => '2026-04-15']);

        $response = $this->actingAs($admin)->put(route('employees.update', $employee), [
            'ac_no' => $employee->ac_no,
            'name' => $employee->name,
            'job_title_id' => $jobTitle->id,
            'basic_salary' => (string) $employee->basic_salary,
            'is_remote_worker' => 0,
            'remote_allowed_dates' => [],
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('employee_remote_work_days', [
            'employee_id' => $employee->id,
            'work_date' => '2026-04-14',
        ]);

        $this->assertDatabaseMissing('employee_remote_work_days', [
            'employee_id' => $employee->id,
            'work_date' => '2026-04-15',
        ]);
    }
}
