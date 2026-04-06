<?php

namespace App\Http\Controllers;

use App\DTOs\AttendanceRowDTO;
use App\Enums\ImportStatus;
use App\Models\AttendanceRecord;
use App\Models\ImportBatch;
use App\Models\Setting;
use App\Services\Attendance\AttendanceCalculationService;
use App\Services\Attendance\PublicHolidayService;
use App\Services\Payroll\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RemoteAttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceCalculationService $calculator,
        private readonly PublicHolidayService $holidayService,
    ) {}

    public function checkIn(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'client_local_date' => ['nullable', 'date_format:Y-m-d'],
            'client_local_time' => ['nullable', 'date_format:H:i:s'],
            'client_timezone' => ['nullable', 'string', 'max:120'],
            'client_timezone_offset_minutes' => ['nullable', 'integer', 'between:-840,840'],
        ]);

        $employee = $request->user()?->employee;
        if (!$employee) {
            return response()->json(['message' => 'لم يتم العثور على ملف الموظف.'], 404);
        }

        if (!$employee->is_remote_worker) {
            return response()->json(['message' => 'هذا الموظف غير مفعّل له الحضور الريموت.'], 422);
        }

        $locations = $employee->locations()->get();
        if ($locations->isEmpty()) {
            return response()->json(['message' => 'لا توجد مواقع مسموح بها لهذا الموظف.'], 422);
        }

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        if (!$this->insideAnyLocation($latitude, $longitude, $locations)) {
            return response()->json(['message' => 'أنت خارج نطاق المواقع المسموح بها.'], 422);
        }

        [$localDate, $localTime, $clientTimezoneMeta] = $this->resolveClientLocalDateTime($data);

        $existing = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->where('date', $localDate)
            ->first();

        if ($existing && $existing->clock_in) {
            return response()->json(['message' => 'تم تسجيل الحضور مسبقاً اليوم.'], 409);
        }

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('attendance-photos', 'public')
            : null;

        AttendanceRecord::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $localDate],
            [
                'clock_in' => $localTime,
                'is_absent' => false,
                'late_minutes' => $this->calculateLateMinutesFromClockIn($employee, $localDate, $localTime),
                'overtime_minutes' => 0,
                'work_minutes' => 0,
                'source' => 'system',
                'type' => 'remote',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'ip_address' => (string) $request->ip(),
                'device_info' => trim(((string) $request->userAgent()) . ' | ' . $clientTimezoneMeta),
                'photo_path' => $photoPath,
                'import_batch_id' => null,
            ]
        );

        return response()->json(['message' => 'تم تسجيل الحضور بنجاح.']);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'client_local_date' => ['nullable', 'date_format:Y-m-d'],
            'client_local_time' => ['nullable', 'date_format:H:i:s'],
            'client_timezone' => ['nullable', 'string', 'max:120'],
            'client_timezone_offset_minutes' => ['nullable', 'integer', 'between:-840,840'],
        ]);

        $employee = $request->user()?->employee;
        if (!$employee) {
            return response()->json(['message' => 'لم يتم العثور على ملف الموظف.'], 404);
        }

        if (!$employee->is_remote_worker) {
            return response()->json(['message' => 'هذا الموظف غير مفعّل له الحضور الريموت.'], 422);
        }

        $locations = $employee->locations()->get();
        if ($locations->isEmpty()) {
            return response()->json(['message' => 'لا توجد مواقع مسموح بها لهذا الموظف.'], 422);
        }

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        if (!$this->insideAnyLocation($latitude, $longitude, $locations)) {
            return response()->json(['message' => 'أنت خارج نطاق المواقع المسموح بها.'], 422);
        }

        [$localDate, $localTime, $clientTimezoneMeta] = $this->resolveClientLocalDateTime($data);

        $record = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->where('date', $localDate)
            ->first();

        if (!$record || !$record->clock_in) {
            return response()->json(['message' => 'لا يمكن تسجيل الانصراف قبل الحضور.'], 409);
        }

        if ($record->clock_out) {
            return response()->json(['message' => 'تم تسجيل الانصراف مسبقاً اليوم.'], 409);
        }

        $calc = $this->calculateMetricsForRemoteRecord(
            $employee->ac_no,
            $employee->name,
            $localDate,
            (string) $record->clock_in,
            $localTime,
            $employee->getShiftOverrides(),
            $employee->isAdminLikeForAttendance()
        );

        $record->update([
            'clock_out' => $localTime,
            'is_absent' => false,
            'late_minutes' => $calc['late_minutes'],
            'overtime_minutes' => $calc['overtime_minutes'],
            'work_minutes' => $calc['work_minutes'],
            'notes' => $calc['notes'],
            'source' => 'system',
            'type' => 'remote',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'ip_address' => (string) $request->ip(),
            'device_info' => trim(((string) $request->userAgent()) . ' | ' . $clientTimezoneMeta),
        ]);

        return response()->json(['message' => 'تم تسجيل الانصراف بنجاح.']);
    }

    private function insideAnyLocation(float $lat, float $lng, Collection $locations): bool
    {
        foreach ($locations as $location) {
            $distance = $this->haversineMeters(
                $lat,
                $lng,
                (float) $location->latitude,
                (float) $location->longitude
            );

            if ($distance <= (float) $location->radius) {
                return true;
            }
        }

        return false;
    }

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }

    private function resolveClientLocalDateTime(array $data): array
    {
        $localDate = $data['client_local_date'] ?? Carbon::today()->toDateString();
        $localTime = $data['client_local_time'] ?? now()->format('H:i:s');

        $timezone = isset($data['client_timezone']) ? (string) $data['client_timezone'] : 'unknown-tz';
        $offset = isset($data['client_timezone_offset_minutes']) ? (int) $data['client_timezone_offset_minutes'] : 0;

        $meta = 'client_tz=' . $timezone . ',offset=' . $offset;

        return [$localDate, $localTime, $meta];
    }

    private function calculateLateMinutesFromClockIn($employee, string $localDate, string $localTime): int
    {
        $defaults = $this->resolveAttendanceSettings();
        $settings = array_merge($defaults, $employee->getShiftOverrides());

        $workStart = $settings['work_start_time'] ?? '09:00';
        $graceMinutes = (int) ($settings['late_grace_minutes'] ?? 30);

        $workStartTime = Carbon::parse($localDate . ' ' . $workStart . ':00');
        $clockInTime = Carbon::parse($localDate . ' ' . $localTime);
        $lateThreshold = $workStartTime->copy()->addMinutes($graceMinutes);

        if ($clockInTime->gt($lateThreshold)) {
            return (int) $clockInTime->diffInMinutes($workStartTime);
        }

        return 0;
    }

    private function calculateMetricsForRemoteRecord(
        string $acNo,
        string $name,
        string $localDate,
        string $clockIn,
        string $clockOut,
        array $employeeOverrides,
        bool $isAdminLike
    ): array {
        $date = Carbon::parse($localDate);

        $row = new AttendanceRowDTO(
            acNo: $acNo,
            name: $name,
            date: $date,
            clockIn: Carbon::parse($localDate . ' ' . $clockIn),
            clockOut: Carbon::parse($localDate . ' ' . $clockOut),
            isAbsent: false,
            notes: null,
        );

        $settings = array_merge($this->resolveAttendanceSettings(), $employeeOverrides);
        $publicHolidays = $this->resolvePublicHolidaysForDate($date);
        $calc = $this->calculator->calculateDay($row, $settings, $publicHolidays);

        if ($isAdminLike) {
            $calc['late_minutes'] = 0;
            $calc['overtime_minutes'] = 0;
        }

        return $calc;
    }

    private function resolveAttendanceSettings(): array
    {
        $allSettings = Setting::getAllAsArray();

        return [
            'work_start_time' => $allSettings['work_start_time'] ?? '09:00',
            'work_end_time' => $allSettings['work_end_time'] ?? '17:00',
            'overtime_start_time' => $allSettings['overtime_start_time'] ?? '17:30',
            'late_grace_minutes' => (int) ($allSettings['late_grace_minutes'] ?? 30),
        ];
    }

    private function resolvePublicHolidaysForDate(Carbon $date): array
    {
        $payrollMonthData = PayrollPeriod::monthForDate($date);

        $batch = ImportBatch::query()
            ->where('month', (int) $payrollMonthData['month'])
            ->where('year', (int) $payrollMonthData['year'])
            ->where('status', ImportStatus::Completed)
            ->latest('id')
            ->first();

        if (!$batch) {
            return [];
        }

        return $this->holidayService->getHolidayDates($batch);
    }
}
