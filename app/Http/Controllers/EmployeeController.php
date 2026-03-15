<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\Employee\EmployeeService;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employeeService
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

        // فترة الراتب: من 22 الشهر السابق حتى 21 الشهر الحالي
        $periodStart = Carbon::create($year, $month, 22)->subMonthNoOverflow()->toDateString();
        $periodEnd   = Carbon::create($year, $month, 21)->toDateString();

        $employee->load(['attendanceRecords' => function ($q) use ($periodStart, $periodEnd) {
            $q->whereBetween('date', [$periodStart, $periodEnd])
              ->orderBy('date');
        }]);

        $records = $employee->attendanceRecords;
        $stats = [
            'present'  => $records->where('is_absent', false)->count(),
            'absent'   => $records->where('is_absent', true)->count(),
            'late'     => $records->sum('late_minutes'),
            'overtime' => $records->sum('overtime_minutes'),
        ];

        // أحدث راتب
        $payroll = $employee->payrollReports()
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        return view('employees.show', compact('employee', 'stats', 'payroll', 'month', 'year', 'periodStart', 'periodEnd'));
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

