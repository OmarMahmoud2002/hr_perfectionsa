<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeRemoteAttendancePageController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()?->employee?->loadMissing('locations', 'remoteWorkDays');
        $allowedLocations = collect();
        $allowRemoteWithoutLocation = $this->allowRemoteWithoutLocation($employee);

        if ($employee) {
            $allowedLocations = $employee->locations;
        }

        $todayRecord = null;
        if ($employee) {
            $todayRecord = AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->where('date', now()->toDateString())
                ->first();
        }

        $remoteRecordsThisMonth = collect();
        $scheduledRemoteDaysThisMonth = collect();
        if ($employee) {
            $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
            $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

            $scheduledRemoteDaysThisMonth = $employee->remoteWorkDays()
                ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
                ->orderBy('work_date')
                ->pluck('work_date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString());

            $remoteRecordsThisMonth = AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('source', 'system')
                ->where('type', 'remote')
                ->orderBy('date')
                ->get(['date', 'clock_in', 'clock_out', 'latitude', 'longitude'])
                ->map(function (AttendanceRecord $record) use ($allowedLocations, $allowRemoteWithoutLocation) {
                    $locationName = $this->resolveMatchedLocationName(
                        $record->latitude !== null ? (float) $record->latitude : null,
                        $record->longitude !== null ? (float) $record->longitude : null,
                        $allowedLocations,
                        $allowRemoteWithoutLocation
                    );

                    return [
                        'date' => Carbon::parse($record->date)->toDateString(),
                        'clock_in' => $record->clock_in,
                        'clock_out' => $record->clock_out,
                        'location_name' => $locationName,
                        'check_in_location_name' => $locationName,
                        'check_out_location_name' => $record->clock_out ? $locationName : '—',
                    ];
                });
        }

        return view('attendance.remote', [
            'employee' => $employee,
            'allowedLocations' => $allowedLocations,
            'todayRecord' => $todayRecord,
            'remoteRecordsThisMonth' => $remoteRecordsThisMonth,
            'scheduledRemoteDaysThisMonth' => $scheduledRemoteDaysThisMonth,
            'allowRemoteWithoutLocation' => $allowRemoteWithoutLocation,
        ]);
    }

    private function resolveMatchedLocationName(?float $lat, ?float $lng, $locations, bool $allowRemoteWithoutLocation): string
    {
        if ($allowRemoteWithoutLocation) {
            return 'بدون عنوان';
        }

        if ($lat === null || $lng === null || $locations->isEmpty()) {
            return 'غير محدد';
        }

        foreach ($locations as $location) {
            $distance = $this->haversineMeters(
                $lat,
                $lng,
                (float) $location->latitude,
                (float) $location->longitude
            );

            if ($distance <= (float) $location->radius) {
                return (string) $location->name;
            }
        }

        return 'خارج المواقع المعتمدة';
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

    private function allowRemoteWithoutLocation($employee): bool
    {
        if ($employee && (bool) ($employee->allow_remote_from_anywhere ?? false)) {
            return true;
        }

        $value = Setting::getValue('allow_remote_without_location', '0');

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
