<?php

namespace App\Http\Controllers;

use App\Enums\ImportStatus;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\PayrollReport;
use App\Models\Setting;
use App\Services\Payroll\PayrollCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollCalculationService $payrollService
    ) {}

    /**
     * قائمة كشوف المرتبات المحسوبة (بحسب الشهر)
     */
    public function index(Request $request): View
    {
        // الشهور التي يوجد لها كشوف مرتبات
        $payrollMonths = PayrollReport::selectRaw('month, year, COUNT(*) as employee_count, SUM(net_salary) as total_net')
            ->groupBy('month', 'year')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        // الشهور التي يوجد لها بيانات حضور ولكن لم يُحسب راتبها بعد
        $importedBatches = ImportBatch::where('status', ImportStatus::Completed)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('payroll.index', compact('payrollMonths', 'importedBatches'));
    }

    /**
     * نموذج حساب المرتبات
     */
    public function showCalculateForm(Request $request): View
    {
        $month      = (int) $request->input('month', now()->month);
        $year       = (int) $request->input('year', now()->year);
        $employeeId = $request->integer('employee_id', 0);

        // التحقق من وجود بيانات حضور للشهر
        $batch = ImportBatch::where('month', $month)
            ->where('year', $year)
            ->where('status', ImportStatus::Completed)
            ->first();

        // الشهور المتاحة
        $availableBatches = ImportBatch::where('status', ImportStatus::Completed)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        // الإعدادات الافتراضية
        $defaultRates = [
            'late_deduction_per_hour'  => Setting::getValue('late_deduction_per_hour', 0),
            'absent_deduction_per_day' => Setting::getValue('absent_deduction_per_day', 0),
            'overtime_rate_per_hour'   => Setting::getValue('overtime_rate_per_hour', 0),
        ];

        // الموظفون الذين لديهم بيانات في هذا الشهر
        $employees = collect();
        if ($batch) {
            $employees = Employee::whereHas('attendanceRecords', function ($q) use ($batch) {
                $q->where('import_batch_id', $batch->id);
            })->get();
        }

        return view('payroll.calculate', compact(
            'month', 'year', 'batch', 'availableBatches', 'defaultRates', 'employees', 'employeeId'
        ));
    }

    /**
     * تنفيذ حساب المرتبات
     */
    public function calculate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month'                    => ['required', 'integer', 'min:1', 'max:12'],
            'year'                     => ['required', 'integer', 'min:2020'],
            'late_deduction_per_hour'  => ['required', 'numeric', 'min:0'],
            'absent_deduction_per_day' => ['required', 'numeric', 'min:0'],
            'overtime_rate_per_hour'   => ['required', 'numeric', 'min:0'],
            'mode'                     => ['required', 'in:all,single'],
            'employee_id'              => ['nullable', 'exists:employees,id'],
        ], [
            'month.required'                    => 'الشهر مطلوب.',
            'year.required'                     => 'السنة مطلوبة.',
            'late_deduction_per_hour.required'  => 'معدل خصم التأخير مطلوب.',
            'late_deduction_per_hour.numeric'   => 'معدل خصم التأخير يجب أن يكون رقماً.',
            'absent_deduction_per_day.required' => 'معدل خصم الغياب مطلوب.',
            'absent_deduction_per_day.numeric'  => 'معدل خصم الغياب يجب أن يكون رقماً.',
            'overtime_rate_per_hour.required'   => 'معدل مكافأة الأوفرتايم مطلوب.',
            'overtime_rate_per_hour.numeric'    => 'معدل مكافأة الأوفرتايم يجب أن يكون رقماً.',
        ]);

        $month = (int) $validated['month'];
        $year  = (int) $validated['year'];

        $rates = [
            'late_deduction_per_hour'  => $validated['late_deduction_per_hour'],
            'absent_deduction_per_day' => $validated['absent_deduction_per_day'],
            'overtime_rate_per_hour'   => $validated['overtime_rate_per_hour'],
        ];

        try {
            if ($validated['mode'] === 'single' && !empty($validated['employee_id'])) {
                $employee = Employee::findOrFail($validated['employee_id']);
                $this->payrollService->calculateForEmployee($employee, $month, $year, $rates);
                $message = "تم حساب راتب الموظف «{$employee->name}» بنجاح.";

                return redirect()
                    ->to(route('payroll.report', ['month' => $month, 'year' => $year]) . '?employee_id=' . $employee->id)
                    ->with('success', $message);
            } else {
                $reports = $this->payrollService->calculateForAll($month, $year, $rates);
                $count   = $reports->count();
                $message = "تم حساب رواتب {$count} موظف بنجاح.";

                return redirect()
                    ->route('payroll.report', ['month' => $month, 'year' => $year])
                    ->with('success', $message);
            }

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء الحساب: ' . $e->getMessage());
        }
    }

    /**
     * عرض كشف مرتبات شهر معين
     */
    public function report(int $month, int $year, Request $request): View
    {
        $reports = $this->payrollService->getMonthlyPayroll($month, $year);

        // تصفية لموظف واحد إذا طُلب ذلك
        $singleEmployee = null;
        $employeeId = $request->integer('employee_id', 0);
        if ($employeeId > 0) {
            $reports       = $reports->filter(fn ($r) => $r->employee_id === $employeeId);
            $singleEmployee = Employee::find($employeeId);
        }

        $summary = $this->payrollService->getSummary($reports);

        $batch = ImportBatch::where('month', $month)
            ->where('year', $year)
            ->where('status', ImportStatus::Completed)
            ->first();

        // الشهور المتاحة للتنقل
        $availableMonths = PayrollReport::selectRaw('month, year')
            ->groupBy('month', 'year')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('payroll.report', compact(
            'reports', 'summary', 'month', 'year', 'batch', 'availableMonths', 'singleEmployee'
        ));
    }

    /**
     * تأمين كشف الراتب
     */
    public function lock(PayrollReport $report): RedirectResponse
    {
        if ($report->is_locked) {
            $this->payrollService->unlockReport($report);
            $msg = "تم إلغاء تأمين كشف راتب الموظف «{$report->employee->name}».";
        } else {
            $this->payrollService->lockReport($report);
            $msg = "تم تأمين كشف راتب الموظف «{$report->employee->name}».";
        }

        return back()->with('success', $msg);
    }

    /**
     * تصدير كشف المرتبات إلى Excel
     */
    public function export(int $month, int $year)
    {
        $reports = $this->payrollService->getMonthlyPayroll($month, $year);

        if ($reports->isEmpty()) {
            return back()->with('error', 'لا توجد بيانات لتصديرها.');
        }

        $monthName = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM_YYYY');
        $fileName  = "payroll_{$monthName}.xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\PayrollExport($reports, $month, $year),
            $fileName
        );
    }
}
