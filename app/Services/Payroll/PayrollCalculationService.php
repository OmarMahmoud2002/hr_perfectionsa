<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\PayrollReport;
use App\Enums\ImportStatus;
use App\Services\Attendance\AbsenceDetectionService;
use App\Services\Attendance\PublicHolidayService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * خدمة حساب المرتبات الشهرية
 * تعتمد على بيانات الحضور من attendance_records
 * وتطبّق معادلة: net = basic - late_deduction - absent_deduction + ot_bonus
 * المعدلات تُحسب تلقائياً من راتب الموظف (salary÷30 لليوم، ×1.5 للتأخير/OT)
 */
class PayrollCalculationService
{
    public function __construct(
        private readonly AbsenceDetectionService $absenceService,
        private readonly PublicHolidayService    $holidayService,
    ) {}

    // =========================================================
    // حساب راتب موظف واحد
    // =========================================================

    /**
     * حساب أو إعادة حساب راتب موظف لشهر معين
     *
     * المعدلات تُحسب تلقائياً من راتب الموظف:
     *   تكلفة اليوم  = الراتب ÷ 30
     *   تكلفة الساعة = تكلفة اليوم ÷ 8
     *   خصم/مكافأة ساعة التأخير أو الأوفرتايم = تكلفة الساعة × 1.5
     *
     * @param  Employee  $employee
     * @param  int       $month
     * @param  int       $year
     * @return PayrollReport
     */
    public function calculateForEmployee(Employee $employee, int $month, int $year): PayrollReport
    {
        // جلب إجازات الشهر
        $batch = ImportBatch::where('month', $month)
            ->where('year', $year)
            ->where('status', ImportStatus::Completed)
            ->first();

        $publicHolidays = $batch ? $this->holidayService->getHolidayDates($batch) : [];

        // إحصائيات الحضور
        $stats = $this->absenceService->getMonthlyStats($employee, $month, $year, $publicHolidays);

        // ============ الحساب المالي ============
        $basicSalary      = (float) $employee->basic_salary;
        $totalLateMinutes = (int) $stats['total_late_minutes'];
        $totalOtMinutes   = (int) $stats['total_overtime_minutes'];
        $absentDays       = $stats['total_absent_days'];

        // معدلات مبنية على راتب الموظف
        // تكلفة اليوم = الراتب ÷ 30
        // تكلفة الساعة = تكلفة اليوم ÷ 8
        // معدل التأخير / الأوفرتايم = تكلفة الساعة × 1.5
        $dailyRate         = $basicSalary / 30;
        $hourlyRate        = $dailyRate / 8;
        $hourlyRateWith1_5 = $hourlyRate * 1.5;

        /*
         * قاعدة الأوفرتايم والتأخير:
         *
         * حالة 1 — التأخير > الأوفرتايم (صافي التأخير موجب):
         *   • الأوفرتايم يُعوّض التأخير أولاً
         *   • إذا كان صافي التأخير < 4 ساعات  → لا يُخصم شيء، لا مكافأة OT
         *   • إذا كان صافي التأخير ≥ 4 ساعات  → يُخصم صافي التأخير × سعر الساعة، لا مكافأة OT
         *
         * حالة 2 — الأوفرتايم ≥ التأخير (الفرق في صالح OT):
         *   • لا يوجد خصم تأخير
         *   • مكافأة OT = إجمالي ساعات الأوفرتايم × سعر الساعة × 1.5 (مهما كانت المدة)
         */
        $netLateMinutes = $totalLateMinutes - $totalOtMinutes;

        if ($netLateMinutes > 0) {
            // صافي التأخير موجب — تطبيق فترة السماح (4 ساعات)
            $graceLimitMinutes    = 4 * 60; // 240 دقيقة
            $effectiveLateMinutes = ($netLateMinutes < $graceLimitMinutes) ? 0 : $netLateMinutes;
            $lateDeduction        = round(($effectiveLateMinutes / 60) * $hourlyRateWith1_5, 2);
            $overtimeBonus        = 0; // الأوفرتايم استُنفد في تعويض التأخير
        } else {
            // صافي الأوفرتايم — لا خصم، ومكافأة على إجمالي ساعات OT
            $lateDeduction = 0;
            $overtimeBonus = round(($totalOtMinutes / 60) * $hourlyRateWith1_5, 2);
        }

        $absentDeduction = round($absentDays * $dailyRate, 2);

        $netSalary = $basicSalary - $lateDeduction - $absentDeduction + $overtimeBonus;

        // لا يمكن أن يكون المرتب النهائي بالسالب
        if ($netSalary < 0) {
            Log::warning("PayrollCalculationService: الراتب النهائي سالب للموظف #{$employee->id} — تم تصحيحه إلى 0", [
                'employee_id' => $employee->id,
                'month'       => $month,
                'year'        => $year,
                'net_salary'  => $netSalary,
            ]);
            $netSalary = 0;
        }

        // حفظ أو تحديث كشف الراتب
        $report = PayrollReport::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'month'       => $month,
                'year'        => $year,
            ],
            [
                'total_working_days'     => $stats['total_working_days'],
                'total_present_days'     => $stats['total_present_days'],
                'total_absent_days'      => $absentDays,
                'total_late_minutes'     => $stats['total_late_minutes'],
                'total_overtime_minutes' => $stats['total_overtime_minutes'],
                'basic_salary'           => $basicSalary,
                'late_deduction'         => $lateDeduction,
                'absent_deduction'       => $absentDeduction,
                'overtime_bonus'         => $overtimeBonus,
                'net_salary'             => $netSalary,
                // لا نعيد ضبط is_locked إذا كان محفوظاً — فقط نحدّثه إذا لم يكن مؤمناً
            ]
        );

        return $report->fresh();
    }

    // =========================================================
    // حساب رواتب جميع الموظفين (Bulk)
    // =========================================================

    /**
     * حساب رواتب جميع الموظفين لشهر معين بدفعة واحدة
     *
     * @param  int    $month
     * @param  int    $year
     * @return Collection<PayrollReport>
     */
    public function calculateForAll(int $month, int $year): Collection
    {
        // التحقق من وجود بيانات للشهر
        $batch = ImportBatch::where('month', $month)
            ->where('year', $year)
            ->where('status', ImportStatus::Completed)
            ->first();

        if (!$batch) {
            throw new \Exception("لا توجد بيانات حضور لشهر {$month}/{$year}. قم برفع ملف الحضور أولاً.");
        }

        // جلب الموظفين الذين لديهم بيانات حضور في هذا الشهر
        $employees = Employee::whereHas('attendanceRecords', function ($q) use ($batch) {
            $q->where('import_batch_id', $batch->id);
        })->get();

        if ($employees->isEmpty()) {
            throw new \Exception("لا يوجد موظفون بسجلات حضور في هذا الشهر.");
        }

        $reports = collect();

        DB::beginTransaction();
        try {
            foreach ($employees as $employee) {
                // تجاهل الرواتب المؤمّنة
                $existingReport = PayrollReport::where('employee_id', $employee->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->where('is_locked', true)
                    ->first();

                if ($existingReport) {
                    $reports->push($existingReport);
                    continue;
                }

                $report = $this->calculateForEmployee($employee, $month, $year);
                $reports->push($report);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $reports;
    }

    // =========================================================
    // تأمين كشف الراتب
    // =========================================================

    /**
     * تأمين كشف الراتب لمنع إعادة الحساب
     */
    public function lockReport(PayrollReport $report): PayrollReport
    {
        $report->update(['is_locked' => true]);
        return $report->fresh();
    }

    /**
     * إلغاء تأمين كشف الراتب
     */
    public function unlockReport(PayrollReport $report): PayrollReport
    {
        $report->update(['is_locked' => false]);
        return $report->fresh();
    }

    // =========================================================
    // جلب كشف المرتبات
    // =========================================================

    /**
     * جلب كشف مرتبات شهر معين
     *
     * @return Collection<PayrollReport>
     */
    public function getMonthlyPayroll(int $month, int $year): Collection
    {
        return PayrollReport::with('employee')
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('employee_id')
            ->get();
    }

    /**
     * إحصائيات ملخصة لكشف المرتبات
     */
    public function getSummary(Collection $reports): array
    {
        return [
            'total_employees'        => $reports->count(),
            'total_basic_salary'     => $reports->sum('basic_salary'),
            'total_late_deduction'   => $reports->sum('late_deduction'),
            'total_absent_deduction' => $reports->sum('absent_deduction'),
            'total_overtime_bonus'   => $reports->sum('overtime_bonus'),
            'total_net_salary'       => $reports->sum('net_salary'),
            'locked_count'           => $reports->where('is_locked', true)->count(),
        ];
    }
}
