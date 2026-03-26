<?php

namespace Tests\Feature;

use App\Models\Employee;
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
}
