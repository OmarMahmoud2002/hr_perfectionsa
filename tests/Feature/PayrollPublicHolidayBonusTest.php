<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\PublicHoliday;
use App\Models\User;
use App\Services\Payroll\PayrollCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPublicHolidayBonusTest extends TestCase
{
    use RefreshDatabase;

    public function test_present_on_public_holiday_adds_one_daily_rate_bonus(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $employee = Employee::factory()->create([
            'basic_salary' => 3000,
        ]);

        $batch = ImportBatch::create([
            'file_name' => 'attendance_march.xlsx',
            'file_path' => 'imports/attendance_march.xlsx',
            'month' => 3,
            'year' => 2026,
            'status' => ImportStatus::Completed,
            'records_count' => 1,
            'employees_count' => 1,
            'uploaded_by' => $admin->id,
        ]);

        PublicHoliday::create([
            'import_batch_id' => $batch->id,
            'date' => '2026-03-05',
            'name' => 'Official Holiday',
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'import_batch_id' => $batch->id,
            'date' => '2026-03-05',
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
            'is_absent' => false,
            'late_minutes' => 0,
            'overtime_minutes' => 0,
            'work_minutes' => 480,
            'source' => 'excel',
            'type' => 'office',
        ]);

        $report = app(PayrollCalculationService::class)
            ->calculateForEmployee($employee, 3, 2026, $batch);

        $this->assertSame('100.00', (string) $report->attendance_bonus);
        $this->assertSame('3100.00', (string) $report->net_salary);
    }

    public function test_absent_on_public_holiday_does_not_add_bonus(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $employee = Employee::factory()->create([
            'basic_salary' => 3000,
        ]);

        $batch = ImportBatch::create([
            'file_name' => 'attendance_march.xlsx',
            'file_path' => 'imports/attendance_march.xlsx',
            'month' => 3,
            'year' => 2026,
            'status' => ImportStatus::Completed,
            'records_count' => 1,
            'employees_count' => 1,
            'uploaded_by' => $admin->id,
        ]);

        PublicHoliday::create([
            'import_batch_id' => $batch->id,
            'date' => '2026-03-05',
            'name' => 'Official Holiday',
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'import_batch_id' => $batch->id,
            'date' => '2026-03-05',
            'clock_in' => null,
            'clock_out' => null,
            'is_absent' => true,
            'late_minutes' => 0,
            'overtime_minutes' => 0,
            'work_minutes' => 0,
            'source' => 'excel',
            'type' => 'office',
        ]);

        $report = app(PayrollCalculationService::class)
            ->calculateForEmployee($employee, 3, 2026, $batch);

        $this->assertSame('0.00', (string) $report->attendance_bonus);
        $this->assertSame('3000.00', (string) $report->net_salary);
    }
}
