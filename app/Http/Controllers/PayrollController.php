<?php

namespace App\Http\Controllers;

use App\Enums\ImportStatus;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\PayrollReport;
use App\Services\Payroll\PayrollCalculationService;
use App\Services\Payroll\PayrollPeriod;
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
        $payrollMonths = PayrollReport::selectRaw('month, year, COUNT(*) as employee_count, SUM(net_salary + extra_bonus - extra_deduction) as total_net')
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
        // (المعدلات تُحسب تلقائياً من راتب كل موظف — لا توجد معدلات ثابتة)

        // الموظفون الذين لديهم بيانات في هذا الشهر
        $employees = collect();
        if ($batch) {
            [$periodStartDate, $periodEndDate] = PayrollPeriod::resolve($month, $year);
            $periodStart = $periodStartDate->toDateString();
            $periodEnd = $periodEndDate->toDateString();

            $employees = Employee::whereHas('attendanceRecords', function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('date', [$periodStart, $periodEnd]);
            })->get();
        }

        return view('payroll.calculate', compact(
            'month', 'year', 'batch', 'availableBatches', 'employees', 'employeeId'
        ));
    }

    /**
     * تنفيذ حساب المرتبات
     */
    public function calculate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month'       => ['required', 'integer', 'min:1', 'max:12'],
            'year'        => ['required', 'integer', 'min:2020'],
            'mode'        => ['required', 'in:all,single'],
            'employee_id' => ['nullable', 'exists:employees,id'],
        ], [
            'month.required' => 'الشهر مطلوب.',
            'year.required'  => 'السنة مطلوبة.',
        ]);

        $month = (int) $validated['month'];
        $year  = (int) $validated['year'];

        try {
            if ($validated['mode'] === 'single' && !empty($validated['employee_id'])) {
                $employee = Employee::findOrFail($validated['employee_id']);
                $this->payrollService->calculateForEmployee($employee, $month, $year);
                $message = "تم حساب راتب الموظف «{$employee->name}» بنجاح.";

                return redirect()
                    ->to(route('payroll.report', ['month' => $month, 'year' => $year]) . '?employee_id=' . $employee->id)
                    ->with('success', $message);
            } else {
                $reports = $this->payrollService->calculateForAll($month, $year);
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
            $singleEmployee = Employee::with('user.profile')->find($employeeId);
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
     * تحديث التسوية الإضافية (بونص أو خصم يدوي)
     */
    public function updateAdjustment(Request $request, PayrollReport $report): RedirectResponse
    {
        if ($report->is_locked) {
            return back()->with('error', 'كشف الراتب مؤمَّن — قم بإلغاء التأمين أولاً قبل تعديل التسوية.');
        }

        $validated = $request->validate([
            'adjustment_type'   => ['required', 'in:bonus,deduction,none'],
            'adjustment_amount' => ['required_unless:adjustment_type,none', 'nullable', 'numeric', 'min:0'],
            'adjustment_note'   => ['nullable', 'string', 'max:255'],
        ], [
            'adjustment_type.required'  => 'نوع التسوية مطلوب.',
            'adjustment_amount.numeric' => 'المبلغ يجب أن يكون رقماً.',
            'adjustment_amount.min'     => 'المبلغ لا يمكن أن يكون بالسالب.',
        ]);

        $extraBonus     = 0;
        $extraDeduction = 0;

        if ($validated['adjustment_type'] === 'bonus') {
            $extraBonus = (float) ($validated['adjustment_amount'] ?? 0);
        } elseif ($validated['adjustment_type'] === 'deduction') {
            $extraDeduction = (float) ($validated['adjustment_amount'] ?? 0);
        }
        // none → يصفّر التسوية

        $report->update([
            'extra_bonus'     => $extraBonus,
            'extra_deduction' => $extraDeduction,
            'adjustment_note' => $validated['adjustment_note'] ?? null,
        ]);

        $name = $report->employee?->name ?? 'الموظف';
        return back()->with('success', "تم تحديث التسوية للموظف «{$name}».");
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

    /**
     * حذف كشف شهر كامل.
     */
    public function destroyMonth(int $month, int $year): RedirectResponse
    {
        if ($month < 1 || $month > 12 || $year < 2020) {
            return back()->with('error', 'قيم الشهر أو السنة غير صحيحة.');
        }

        $deleted = PayrollReport::where('month', $month)
            ->where('year', $year)
            ->delete();

        if ($deleted === 0) {
            return back()->with('error', 'لا توجد بيانات رواتب لهذا الشهر.');
        }

        $monthLabel = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY');

        return back()->with('success', "تم حذف كشف رواتب شهر {$monthLabel} بنجاح.");
    }
}
