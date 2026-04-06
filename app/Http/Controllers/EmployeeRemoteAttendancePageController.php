<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeRemoteAttendancePageController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()?->employee?->loadMissing('locations');

        $todayRecord = null;
        if ($employee) {
            $todayRecord = AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->where('date', now()->toDateString())
                ->first();
        }

        $remoteRecordsThisMonth = collect();
        if ($employee) {
            $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
            $endOfMonth = Carbon::now()->endOfMonth()->toDateString();
            $allowedLocations = $employee->locations;

            $remoteRecordsThisMonth = AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('source', 'system')
                ->where('type', 'remote')
                ->orderBy('date')
                ->get(['date', 'clock_in', 'clock_out', 'latitude', 'longitude'])
                ->map(function (AttendanceRecord $record) use ($allowedLocations) {
                    $locationName = $this->resolveMatchedLocationName(
                        $record->latitude !== null ? (float) $record->latitude : null,
                        $record->longitude !== null ? (float) $record->longitude : null,
                        $allowedLocations
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
            'todayRecord' => $todayRecord,
            'remoteRecordsThisMonth' => $remoteRecordsThisMonth,
        ]);
    }

    private function resolveMatchedLocationName(?float $lat, ?float $lng, $locations): string
    {
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
}
