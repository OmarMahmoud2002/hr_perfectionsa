<?php

namespace App\Http\Controllers;

use App\Enums\ImportStatus;
use App\Models\ImportBatch;
use App\Services\Attendance\AbsenceDetectionService;
use App\Services\Attendance\PublicHolidayService;
use App\Services\Dashboard\DashboardStatisticsService;
use App\Services\Payroll\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatisticsService $statsService,
        private readonly AbsenceDetectionService $absenceService,
        private readonly PublicHolidayService $holidayService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('employee');

        if ($user->isEmployee()) {
            $monthData = PayrollPeriod::monthForDate(now());
            $defaultMonth = (int) $monthData['month'];
            $defaultYear = (int) $monthData['year'];

            $cookieMonth = (int) $request->cookie('employee_stats_month', 0);
            $cookieYear = (int) $request->cookie('employee_stats_year', 0);

            $month = $request->has('month')
                ? (int) $request->input('month')
                : ($cookieMonth > 0 ? $cookieMonth : $defaultMonth);

            $year = $request->has('year')
                ? (int) $request->input('year')
                : ($cookieYear > 0 ? $cookieYear : $defaultYear);

            if ($month < 1 || $month > 12) {
                $month = $defaultMonth;
            }

            if ($year < 2000 || $year > 2100) {
                $year = $defaultYear;
            }

            Cookie::queue(cookie('employee_stats_month', (string) $month, 60 * 24 * 365));
            Cookie::queue(cookie('employee_stats_year', (string) $year, 60 * 24 * 365));

            [$periodStartDate, $periodEndDate] = PayrollPeriod::resolve($month, $year);

            $stats = null;
            $dailyBreakdown = collect();

            if ($user->employee) {
                $batch = ImportBatch::where('month', $month)
                    ->where('year', $year)
                    ->where('status', ImportStatus::Completed)
                    ->latest('id')
                    ->first();

                $publicHolidays = $batch ? $this->holidayService->getHolidayDates($batch) : [];

                $monthlyStats = $this->absenceService->getMonthlyStats($user->employee, $month, $year, $publicHolidays);
                $stats = [
                    'present' => $monthlyStats['total_present_days'],
                    'absent' => $monthlyStats['total_absent_days'],
                    'late_minutes' => $monthlyStats['total_late_minutes'],
                    'overtime_minutes' => $monthlyStats['total_overtime_minutes'],
                ];

                $dailyBreakdown = $this->absenceService->getDailyBreakdown($user->employee, $month, $year, $publicHolidays);
            }

            return view('dashboard.employee', [
                'month' => $month,
                'year' => $year,
                'periodStart' => $periodStartDate->toDateString(),
                'periodEnd' => $periodEndDate->toDateString(),
                'stats' => $stats,
                'dailyBreakdown' => $dailyBreakdown,
            ]);
        }

        $stats = $this->statsService->getStats();

        $monthly = $stats['current_month_stats'];
        $currentPayrollMonth = PayrollPeriod::monthForDate(now());
        $dashboardMonthLabel = Carbon::create(
            $currentPayrollMonth['year'],
            $currentPayrollMonth['month'],
            1
        )->locale('ar')->isoFormat('MMMM YYYY');

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
            'dashboardMonthLabel' => $dashboardMonthLabel,
        ]);
    }
}
