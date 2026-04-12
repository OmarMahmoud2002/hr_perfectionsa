<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeLeaveProfile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = $this->makeAdmin();

        $payload = [
            'work_start_time'          => '08:30',
            'work_end_time'            => '16:30',
            'overtime_start_time'      => '17:00',
            'late_grace_minutes'       => 20,
            'working_days_per_month'   => 26,
            'working_hours_per_day'    => 8,
        ];

        $response = $this->actingAs($admin)
            ->put(route('settings.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        foreach ($payload as $key => $value) {
            $this->assertDatabaseHas('settings', [
                'key'   => $key,
                'value' => (string) $value,
                'group' => 'attendance',
            ]);
        }
    }

    public function test_validation_prevents_invalid_settings(): void
    {
        $admin = $this->makeAdmin();
        $initialSettingsCount = Setting::count();

        $response = $this->actingAs($admin)
            ->from(route('settings.index'))
            ->put(route('settings.update'), [
                'work_start_time'          => 'invalid',
                'work_end_time'            => '',
                'overtime_start_time'      => '25:61',
                'late_deduction_per_hour'  => -1,
                'absent_deduction_per_day' => -5,
                'overtime_rate_per_hour'   => -3,
                'late_grace_minutes'       => 999,
                'working_days_per_month'   => 0,
                'working_hours_per_day'    => 0,
            ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('settings', $initialSettingsCount);
    }

    public function test_settings_update_does_not_override_employee_leave_profiles(): void
    {
        $admin = $this->makeAdmin();

        $employeeWithNoProfile = Employee::factory()->create(['is_active' => true]);

        $employeeWithPartialProfile = Employee::factory()->create(['is_active' => true]);
        EmployeeLeaveProfile::query()->create([
            'employee_id' => $employeeWithPartialProfile->id,
            'employment_start_date' => now()->subDays(200)->toDateString(),
            'required_work_days_before_leave' => null,
            'annual_leave_quota' => null,
        ]);

        $employeeWithCustomProfile = Employee::factory()->create(['is_active' => true]);
        EmployeeLeaveProfile::query()->create([
            'employee_id' => $employeeWithCustomProfile->id,
            'employment_start_date' => now()->subDays(250)->toDateString(),
            'required_work_days_before_leave' => 90,
            'annual_leave_quota' => 15,
        ]);

        $payload = [
            'work_start_time' => '08:30',
            'work_end_time' => '16:30',
            'overtime_start_time' => '17:00',
            'late_grace_minutes' => 20,
            'working_days_per_month' => 26,
            'working_hours_per_day' => 8,
        ];

        $this->actingAs($admin)
            ->put(route('settings.update'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('employee_leave_profiles', [
            'employee_id' => $employeeWithNoProfile->id,
        ]);

        $this->assertDatabaseHas('employee_leave_profiles', [
            'employee_id' => $employeeWithPartialProfile->id,
            'required_work_days_before_leave' => null,
            'annual_leave_quota' => null,
        ]);

        $this->assertDatabaseHas('employee_leave_profiles', [
            'employee_id' => $employeeWithCustomProfile->id,
            'required_work_days_before_leave' => 90,
            'annual_leave_quota' => 15,
        ]);
    }
}
