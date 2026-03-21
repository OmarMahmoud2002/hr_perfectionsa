<?php

namespace App\Http\Controllers;

use App\Enums\ImportStatus;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Services\Attendance\AbsenceDetectionService;
use App\Services\Attendance\PublicHolidayService;
use App\Services\Employee\EmployeeService;
use App\Services\Payroll\PayrollPeriod;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employeeService,
        private readonly AbsenceDetectionService $absenceService,
        private readonly PublicHolidayService $holidayService,
    ) {}

    /**
     * عرض قائمة الموظفين
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status']);
        $employees = $this->employeeService->getEmployees($filters, perPage: 15);

        return view('employees.index', compact('employees', 'filters'));
    }

    /**
     * عرض نموذج إضافة موظف
     */
    public function create(): View
    {
        return view('employees.create');
    }

    /**
     * حفظ موظف جديد
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = $this->employeeService->create($request->validated());

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', "تم إضافة الموظف «{$employee->name}» بنجاح.");
    }

    /**
     * عرض تفاصيل موظف
     */
    public function show(Employee $employee, Request $request): View
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        [$periodStartDate, $periodEndDate] = PayrollPeriod::resolve($month, $year);
        $periodStart = $periodStartDate->toDateString();
        $periodEnd   = $periodEndDate->toDateString();

        // جلب batch والإجازات الرسمية
        $batch = ImportBatch::where('month', $month)
            ->where('year', $year)
            ->where('status', ImportStatus::Completed)
            ->first();

        $publicHolidays = $batch ? $this->holidayService->getHolidayDates($batch) : [];

        // التفاصيل اليومية مع تطبيق قاعدة الإجازة الأسبوعية
        $dailyBreakdown = $this->absenceService->getDailyBreakdown($employee, $month, $year, $publicHolidays);

        // الإحصائيات الصحيحة
        $monthlyStats = $this->absenceService->getMonthlyStats($employee, $month, $year, $publicHolidays);
        $stats = [
            'present'      => $monthlyStats['total_present_days'],
            'absent'       => $monthlyStats['total_absent_days'],
            'late'         => $monthlyStats['total_late_minutes'],
            'overtime'     => $monthlyStats['total_overtime_minutes'],
            'weekly_leave' => $monthlyStats['total_weekly_leave_days'],
        ];

        // أحدث راتب
        $payroll = $employee->payrollReports()
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        return view('employees.show', compact('employee', 'stats', 'payroll', 'month', 'year', 'periodStart', 'periodEnd', 'dailyBreakdown'));
    }

    /**
     * عرض نموذج تعديل موظف
     */
    public function edit(Employee $employee): View
    {
        return view('employees.edit', compact('employee'));
    }

    /**
     * تحديث بيانات موظف
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->employeeService->update($employee, $request->validated());

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', "تم تحديث بيانات الموظف «{$employee->name}» بنجاح.");
    }

    /**
     * تعطيل / حذف ناعم للموظف
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $name = $employee->name;
        $this->employeeService->deactivate($employee);

        return redirect()
            ->route('employees.index')
            ->with('success', "تم تعطيل الموظف «{$name}» بنجاح.");
    }
}

