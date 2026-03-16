<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Enums\ImportStatus;
use App\Services\Attendance\AbsenceDetectionService;
use App\Services\Attendance\PublicHolidayService;
use Carbon\Carbon;
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

    /**
     * تغيير حالة يوم معين لموظف (خاص بالمديرين)
     * يدعم: present | absent | weekly_leave | public_holiday | auto (إزالة التجاوز اليدوي)
     *
     * المبدأ الأساسي: manual_status فقط هو الذي يتغير.
     * البيانات الأصلية (clock_in, clock_out, is_absent, late_minutes, ... )
     * لا تُمس أبداً حتى تبقى سليمة عند الإعادة للحساب التلقائي.
     */
    public function updateDayStatus(Employee $employee, string $date, Request $request)
    {
        $request->validate([
            'status' => 'required|in:present,absent,weekly_leave,public_holiday,auto',
        ]);

        $status  = $request->input('status');
        $dateObj = Carbon::parse($date);

        // تحديد الـ batch المرتبط بهذا التاريخ (22 سابق → 21 حالي)
        if ($dateObj->day >= 22) {
            $payrollMonth = $dateObj->month === 12 ? 1 : $dateObj->month + 1;
            $payrollYear  = $dateObj->month === 12 ? $dateObj->year + 1 : $dateObj->year;
        } else {
            $payrollMonth = $dateObj->month;
            $payrollYear  = $dateObj->year;
        }

        $batch = ImportBatch::where('month', $payrollMonth)
            ->where('year', $payrollYear)
            ->where('status', ImportStatus::Completed)
            ->first();

        if (! $batch) {
            return back()->with('error', 'لا توجد دفعة استيراد مكتملة لهذا التاريخ.');
        }

        if ($status === 'auto') {
            // إعادة للحساب التلقائي: امسح manual_status فقط
            $record = AttendanceRecord::where('employee_id', $employee->id)
                ->where('date', $date)
                ->first();

            if ($record) {
                // إذا كان السجل مجرد placeholder أنشأناه (لا بيانات أصلية) → احذفه كلياً
                // علامة الـ placeholder: clock_in=null و clock_out=null و work=0 و notes فارغة و is_absent=false
                $isPlaceholder = $record->clock_in === null
                    && $record->clock_out === null
                    && $record->work_minutes === 0
                    && $record->late_minutes === 0
                    && $record->overtime_minutes === 0
                    && empty($record->notes)
                    && ! $record->is_absent;

                if ($isPlaceholder) {
                    $record->delete();
                } else {
                    $record->update(['manual_status' => null]);
                }
            }

            return back()->with('success', 'تم إعادة الحالة إلى الحساب التلقائي.');
        }

        // نُحدّث manual_status فقط — البيانات الأصلية تبقى كما هي
        AttendanceRecord::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date'        => $date,
            ],
            [
                'manual_status'   => $status,
                'import_batch_id' => $batch->id,
            ]
        );

        $statusLabels = [
            'present'        => 'حاضر',
            'absent'         => 'غائب',
            'weekly_leave'   => 'إجازة أسبوعية',
            'public_holiday' => 'إجازة رسمية',
        ];

        return back()->with('success', 'تم تغيير الحالة إلى «' . $statusLabels[$status] . '» بنجاح.');
    }
}
