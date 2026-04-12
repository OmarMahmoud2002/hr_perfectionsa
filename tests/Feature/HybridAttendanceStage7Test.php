<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HybridAttendanceStage7Test extends TestCase
{
    use RefreshDatabase;

    private function makeAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'must_change_password' => false,
        ]);
    }

    private function makeEmployeeUser(): array
    {
        $employee = Employee::factory()->create([
            'is_remote_worker' => true,
        ]);

        $employee->remoteWorkDays()->create([
            'work_date' => now()->toDateString(),
        ]);

        $user = User::factory()->create([
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => false,
        ]);

        return [$user, $employee];
    }

    public function test_employee_cannot_check_in_on_unscheduled_remote_day(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();

        $employee->remoteWorkDays()->delete();
        $employee->remoteWorkDays()->create([
            'work_date' => Carbon::tomorrow()->toDateString(),
        ]);

        $location = Location::create([
            'name' => 'مكتب التجربة',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'radius' => 250,
        ]);

        $employee->locations()->sync([$location->id]);

        $response = $this->actingAs($user)->postJson(route('attendance.check-in'), [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
            'client_local_date' => now()->toDateString(),
            'client_local_time' => '09:42:13',
            'client_timezone' => 'Asia/Riyadh',
            'client_timezone_offset_minutes' => 180,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'هذا اليوم غير مجدول كدوام ريموت لك.']);
    }

    public function test_employee_can_check_in_inside_assigned_location(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();

        $location = Location::create([
            'name' => 'مكتب التجربة',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'radius' => 250,
        ]);

        $employee->locations()->sync([$location->id]);

        $clientDate = now()->toDateString();
        $clientTime = '09:42:13';

        $response = $this->actingAs($user)->postJson(route('attendance.check-in'), [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
            'client_local_date' => $clientDate,
            'client_local_time' => $clientTime,
            'client_timezone' => 'Asia/Riyadh',
            'client_timezone_offset_minutes' => 180,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'تم تسجيل الحضور بنجاح.']);

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $employee->id,
            'date' => $clientDate,
            'clock_in' => $clientTime,
            'source' => 'system',
            'type' => 'remote',
        ]);
    }

    public function test_employee_cannot_check_in_outside_assigned_locations(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();

        $location = Location::create([
            'name' => 'فرع القاهرة',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'radius' => 100,
        ]);

        $employee->locations()->sync([$location->id]);

        $response = $this->actingAs($user)->postJson(route('attendance.check-in'), [
            'latitude' => 29.9000,
            'longitude' => 31.0000,
            'accuracy' => 15,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'أنت خارج نطاق المواقع المسموح بها.']);

        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'source' => 'system',
            'type' => 'remote',
        ]);
    }

    public function test_employee_can_check_in_even_when_accuracy_is_high(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();

        $location = Location::create([
            'name' => 'فرع صالح للتجربة',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'radius' => 250,
        ]);

        $employee->locations()->sync([$location->id]);

        $clientDate = now()->toDateString();
        $clientTime = '09:50:00';

        $response = $this->actingAs($user)->postJson(route('attendance.check-in'), [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 950,
            'client_local_date' => $clientDate,
            'client_local_time' => $clientTime,
            'client_timezone' => 'Africa/Cairo',
            'client_timezone_offset_minutes' => 120,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $employee->id,
            'date' => $clientDate,
            'clock_in' => $clientTime,
            'source' => 'system',
            'type' => 'remote',
        ]);
    }

    public function test_employee_can_check_out_after_check_in(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();

        $clientDate = now()->toDateString();
        $clientOutTime = '18:17:33';

        $location = Location::create([
            'name' => 'المقر الرئيسي',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'radius' => 300,
        ]);

        $employee->locations()->sync([$location->id]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'date' => $clientDate,
            'clock_in' => '09:00:00',
            'clock_out' => null,
            'is_absent' => false,
            'late_minutes' => 0,
            'overtime_minutes' => 0,
            'work_minutes' => 0,
            'import_batch_id' => null,
            'source' => 'system',
            'type' => 'remote',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'ip_address' => '127.0.0.1',
            'device_info' => 'test-device',
        ]);

        $response = $this->actingAs($user)->postJson(route('attendance.check-out'), [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
            'client_local_date' => $clientDate,
            'client_local_time' => $clientOutTime,
            'client_timezone' => 'Africa/Cairo',
            'client_timezone_offset_minutes' => 120,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'تم تسجيل الانصراف بنجاح.']);

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $clientDate)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame($clientOutTime, $record->clock_out);
        $this->assertSame(0, (int) $record->late_minutes);
        $this->assertGreaterThan(0, (int) $record->overtime_minutes);
        $this->assertGreaterThan(0, (int) $record->work_minutes);
    }

    public function test_remote_attendance_calculates_late_and_overtime_like_normal_days(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();

        $employee->update([
            'work_start_time' => '09:00',
            'late_grace_minutes' => 30,
            'overtime_start_time' => '17:30',
        ]);

        $location = Location::create([
            'name' => 'موقع الاختبار',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'radius' => 300,
        ]);

        $employee->locations()->sync([$location->id]);

        $clientDate = now()->toDateString();

        $checkIn = $this->actingAs($user)->postJson(route('attendance.check-in'), [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 10,
            'client_local_date' => $clientDate,
            'client_local_time' => '10:00:00',
            'client_timezone' => 'Africa/Cairo',
            'client_timezone_offset_minutes' => 120,
        ]);

        $checkIn->assertOk();

        $checkOut = $this->actingAs($user)->postJson(route('attendance.check-out'), [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 10,
            'client_local_date' => $clientDate,
            'client_local_time' => '18:30:00',
            'client_timezone' => 'Africa/Cairo',
            'client_timezone_offset_minutes' => 120,
        ]);

        $checkOut->assertOk();

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $clientDate)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame(60, (int) $record->late_minutes);
        $this->assertSame(60, (int) $record->overtime_minutes);
        $this->assertSame(510, (int) $record->work_minutes);
        $this->assertSame('system', $record->source);
        $this->assertSame('remote', $record->type);
    }

    public function test_non_remote_employee_cannot_use_remote_check_in(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();
        $employee->update(['is_remote_worker' => false]);

        $location = Location::create([
            'name' => 'مكتب الجيزة',
            'latitude' => 30.0131,
            'longitude' => 31.2089,
            'radius' => 150,
        ]);

        $employee->locations()->sync([$location->id]);

        $response = $this->actingAs($user)->postJson(route('attendance.check-in'), [
            'latitude' => 30.0131,
            'longitude' => 31.2089,
            'accuracy' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'هذا الموظف غير مفعّل له الحضور الريموت.']);
    }

    public function test_remote_attendance_page_shows_check_in_button_for_remote_employee(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();

        $location = Location::create([
            'name' => 'فرع التجمع',
            'latitude' => 30.0288,
            'longitude' => 31.4913,
            'radius' => 200,
        ]);

        $employee->locations()->sync([$location->id]);

        $this->actingAs($user)
            ->get(route('attendance.remote.page'))
            ->assertOk()
            ->assertSee('تسجيل الحضور', false)
            ->assertSee('المواقع المسموح بها لك', false);
    }

    public function test_attendance_and_payroll_screens_still_work_for_admin(): void
    {
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)
            ->get(route('attendance.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('payroll.index'))
            ->assertOk();
    }

    public function test_remote_employee_is_included_in_attendance_report_for_month(): void
    {
        $admin = $this->makeAdminUser();
        [$user, $employee] = $this->makeEmployeeUser();

        ImportBatch::create([
            'file_name' => 'attendance.xlsx',
            'file_path' => 'imports/attendance.xlsx',
            'month' => now()->month,
            'year' => now()->year,
            'status' => ImportStatus::Completed,
            'records_count' => 1,
            'employees_count' => 1,
            'uploaded_by' => $admin->id,
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:15:00',
            'clock_out' => '17:05:00',
            'is_absent' => false,
            'late_minutes' => 0,
            'overtime_minutes' => 0,
            'work_minutes' => 470,
            'import_batch_id' => null,
            'source' => 'system',
            'type' => 'remote',
        ]);

        $this->actingAs($admin)
            ->get(route('attendance.report', ['month' => now()->month, 'year' => now()->year]))
            ->assertOk()
            ->assertSee($employee->name, false);
    }

    public function test_remote_employee_is_included_in_payroll_calculate_form_for_month(): void
    {
        $admin = $this->makeAdminUser();
        [$user, $employee] = $this->makeEmployeeUser();

        ImportBatch::create([
            'file_name' => 'attendance.xlsx',
            'file_path' => 'imports/attendance.xlsx',
            'month' => now()->month,
            'year' => now()->year,
            'status' => ImportStatus::Completed,
            'records_count' => 1,
            'employees_count' => 1,
            'uploaded_by' => $admin->id,
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'clock_in' => '10:00:00',
            'clock_out' => '18:00:00',
            'is_absent' => false,
            'late_minutes' => 0,
            'overtime_minutes' => 0,
            'work_minutes' => 480,
            'import_batch_id' => null,
            'source' => 'system',
            'type' => 'remote',
        ]);

        $this->actingAs($admin)
            ->get(route('payroll.calculate.form', ['month' => now()->month, 'year' => now()->year]))
            ->assertOk()
            ->assertSee($employee->name, false);
    }
}
