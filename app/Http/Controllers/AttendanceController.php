<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ImportBatch;
use App\Enums\ImportStatus;
use App\Services\Attendance\AbsenceDetectionService;
use App\Services\Attendance\PublicHolidayService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AbsenceDetectionService $absenceService,
        private readonly PublicHolidayService    $holidayService,
    ) {}

    /**
     * عرض قائمة الدفعات (شهور) المستوردة
     */
    public function index(Request $request): View
    {
        $batches = ImportBatch::where('status', ImportStatus::Completed)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('attendance.index', compact('batches'));
    }

    /**
     * تقرير الحضور الشهري لجميع الموظفين
     */
    public function report(Request $request): View
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        // الدفعة المقابلة للشهر والسنة
        $batch = ImportBatch::where('month', $month)
            ->where('year', $year)
            ->where('status', ImportStatus::Completed)
            ->first();

        $employeeStats  = collect();
        $publicHolidays = [];

        if ($batch) {
            $publicHolidays = $this->holidayService->getHolidayDates($batch);

            $employees = Employee::whereHas('attendanceRecords', function ($q) use ($batch) {
                $q->where('import_batch_id', $batch->id);
            })->orderBy('name')->get();

            $employeeStats = $this->absenceService->getBulkMonthlyStats(
                $employees,
                $month,
                $year,
                $publicHolidays
            );
        }

        // قائمة الشهور المتاحة للفلترة
        $availableMonths = ImportBatch::where('status', ImportStatus::Completed)
            ->orderByDesc('year')->orderByDesc('month')
            ->get(['month', 'year']);

        return view('attendance.report', compact(
            'batch',
            'employeeStats',
            'publicHolidays',
            'month',
            'year',
            'availableMonths'
        ));
    }

    /**
     * تقرير الحضور التفصيلي لموظف واحد
     */
    public function employeeReport(Employee $employee, Request $request): View
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $batch = ImportBatch::where('month', $month)
            ->where('year', $year)
            ->where('status', ImportStatus::Completed)
            ->first();

        $publicHolidays = $batch ? $this->holidayService->getHolidayDates($batch) : [];

        // تفاصيل كل يوم في الشهر
        $dailyBreakdown = $this->absenceService->getDailyBreakdown($employee, $month, $year, $publicHolidays);

        // ملخص إحصائيات الشهر
        $stats = $this->absenceService->getMonthlyStats($employee, $month, $year, $publicHolidays);

        // قائمة الشهور المتاحة
        $availableMonths = ImportBatch::where('status', ImportStatus::Completed)
            ->orderByDesc('year')->orderByDesc('month')
            ->get(['month', 'year']);

        return view('attendance.employee', compact(
            'employee',
            'month',
            'year',
            'batch',
            'dailyBreakdown',
            'stats',
            'publicHolidays',
            'availableMonths'
        ));
    }

    /**
     * تصدير تقرير حضور موظف واحد إلى Excel
     */
    public function exportEmployee(Employee $employee, Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $batch = ImportBatch::where('month', $month)
            ->where('year', $year)
            ->where('status', ImportStatus::Completed)
            ->first();

        $publicHolidays = $batch ? $this->holidayService->getHolidayDates($batch) : [];

        $dailyBreakdown = $this->absenceService->getDailyBreakdown($employee, $month, $year, $publicHolidays);
        $stats          = $this->absenceService->getMonthlyStats($employee, $month, $year, $publicHolidays);

        if ($dailyBreakdown->isEmpty()) {
            return back()->with('error', 'لا توجد بيانات لهذا الشهر.');
        }

        $monthName = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM_YYYY');
        $fileName  = "attendance_{$employee->ac_no}_{$monthName}.xlsx";

        return Excel::download(
            new \App\Exports\AttendanceEmployeeExport($employee, $dailyBreakdown, $stats, $month, $year),
            $fileName
        );
    }
}
