<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardStatisticsService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatisticsService $statsService
    ) {}

    public function index()
    {
        $stats = $this->statsService->getStats();

        $monthly = $stats['current_month_stats'];

        return view('dashboard.index', [
            'totalEmployees'    => $stats['active_employees'],
            'lastBatch'         => $stats['last_batch'],
            'recentBatches'     => $stats['recent_batches'],
            'topLateEmployees'  => $stats['top_late_employees'],
            'topOTEmployees'    => $stats['top_ot_employees'],
            // إحصائيات الشهر الحالي
            'attendanceRate'    => $monthly['attendance_rate'],
            'totalLateHours'    => $monthly['total_late_hours'],
            'totalOTHours'      => $monthly['total_ot_hours'],
            'totalAbsentDays'   => $monthly['total_absent_days'],
            'hasCurrentData'    => $monthly['has_data'],
        ]);
    }
}
