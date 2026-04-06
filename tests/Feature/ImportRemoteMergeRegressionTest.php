<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\Attendance\AttendanceCalculationService;
use App\Services\Attendance\PublicHolidayService;
use App\Services\Dashboard\DashboardStatisticsService;
use App\Services\Employee\EmployeeService;
use App\Services\Excel\ExcelParserService;
use App\Services\Excel\ExcelReaderService;
use App\Services\Import\ImportService;
use App\Services\Payroll\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ImportRemoteMergeRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_import_does_not_override_existing_remote_day_for_same_employee_and_date(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);

        $employee = Employee::factory()->create([
            'ac_no' => '123',
            'name' => 'Remote User',
            'is_remote_worker' => true,
        ]);

        $date = Carbon::parse('2026-03-10');
        $payrollMonth = PayrollPeriod::monthForDate($date);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'date' => $date->toDateString(),
            'clock_in' => '10:00:00',
            'clock_out' => '18:30:00',
            'is_absent' => false,
            'late_minutes' => 60,
            'overtime_minutes' => 60,
            'work_minutes' => 510,
            'notes' => null,
            'import_batch_id' => null,
            'source' => 'system',
            'type' => 'remote',
        ]);

        $batch = ImportBatch::create([
            'file_name' => 'attendance.xlsx',
            'file_path' => 'imports/fake.xlsx',
            'month' => (int) $payrollMonth['month'],
            'year' => (int) $payrollMonth['year'],
            'status' => ImportStatus::Pending,
            'records_count' => 0,
            'employees_count' => 0,
            'uploaded_by' => $admin->id,
        ]);

        $reader = Mockery::mock(ExcelReaderService::class);
        $parser = Mockery::mock(ExcelParserService::class);
        $employeeService = Mockery::mock(EmployeeService::class);
        $holidayService = Mockery::mock(PublicHolidayService::class);
        $dashboardStats = Mockery::mock(DashboardStatisticsService::class);

        $reader->shouldReceive('processRowsFromPath')
            ->once()
            ->withArgs(function (string $filePath, callable $onChunk, int $chunkSize) {
                if ($filePath !== 'imports/fake.xlsx' || $chunkSize !== 1000) {
                    return false;
                }

                $onChunk([
                    ['ac_no', 'name', 'date', 'clock_in', 'clock_out'],
                    ['123', 'Remote User', '2026-03-10', null, null],
                ]);

                return true;
            });

        $parser->shouldReceive('detectColumns')
            ->once()
            ->andReturn([
                'ac_no' => 0,
                'name' => 1,
                'date' => 2,
                'clock_in' => 3,
                'clock_out' => 4,
            ]);

        $employeeService->shouldReceive('findOrCreateFromExcel')
            ->once()
            ->with('123', 'Remote User')
            ->andReturn($employee);

        $holidayService->shouldReceive('getHolidayDates')
            ->once()
            ->withArgs(fn (ImportBatch $b) => $b->id === $batch->id)
            ->andReturn([]);

        $dashboardStats->shouldReceive('clearCache')->once();

        $service = new ImportService(
            $reader,
            $parser,
            $employeeService,
            app(AttendanceCalculationService::class),
            $holidayService,
            $dashboardStats,
        );

        $service->processImport($batch);

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', '2026-03-10')
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('system', $record->source);
        $this->assertSame('remote', $record->type);
        $this->assertSame('10:00:00', (string) $record->clock_in);
        $this->assertSame('18:30:00', (string) $record->clock_out);
        $this->assertSame(60, (int) $record->late_minutes);
        $this->assertSame(60, (int) $record->overtime_minutes);
        $this->assertSame(510, (int) $record->work_minutes);

        $this->assertSame(1, AttendanceRecord::query()->count());

        $batch->refresh();
        $this->assertTrue($batch->isCompleted());
    }
}
