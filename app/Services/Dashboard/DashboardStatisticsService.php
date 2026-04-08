<?php

namespace App\Services\Dashboard;

use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\AttendanceRecord;
use App\Models\PayrollReport;
use App\Models\User;
use App\Services\Department\DepartmentScopeService;
use App\Services\Payroll\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatisticsService
{
    public function __construct(
        private readonly DepartmentScopeService $departmentScopeService,
    ) {}

    /**
     * مدة الـ Cache بالثواني (5 دقائق)
     */
    private const CACHE_TTL = 300;

    /**
     * جلب جميع إحصائيات لوحة التحكم
     */
    public function getStats(?User $actor = null): array
    {
        $cacheKey = $actor
            ? "dashboard_stats_user_{$actor->id}_{$actor->role}"
            : 'dashboard_stats';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($actor) {
            return [
                'total_employees'    => $this->getTotalEmployees($actor),
                'active_employees'   => $this->getActiveEmployees($actor),
                'last_batch'         => $this->getLastBatch(),
                'current_month_stats'=> $this->getCurrentMonthStats($actor),
                'recent_batches'     => $this->getRecentBatches(),
                'top_late_employees' => $this->getTopLateEmployees(actor: $actor),
                'top_ot_employees'   => $this->getTopOTEmployees(actor: $actor),
                'top_work_employees' => $this->getTopWorkEmployees(actor: $actor),
            ];
        });
    }

    /**
     * مسح الـ Cache عند تحديث البيانات
     */
    public function clearCache(): void
    {
        Cache::forget('dashboard_stats');
    }

    // ========================
    // Private Methods
    // ========================

    private function getTotalEmployees(?User $actor = null): int
    {
        $query = Employee::query();

        if ($actor !== null) {
            $this->departmentScopeService->applyEmployeeScope($query, $actor);
        }

        return $query->count();
    }

    private function getActiveEmployees(?User $actor = null): int
    {
        $query = Employee::active();

        if ($actor !== null) {
            $this->departmentScopeService->applyEmployeeScope($query, $actor);
        }

        return $query->count();
    }

    private function getLastBatch(): ?ImportBatch
    {
        return ImportBatch::with('uploader')
            ->latest()
            ->first();
    }

    private function getCurrentMonthStats(?User $actor = null): array
    {
        $currentPayrollMonth = PayrollPeriod::monthForDate(Carbon::now());
        $currentMonth = $currentPayrollMonth['month'];
        $currentYear  = $currentPayrollMonth['year'];
        [$periodStart, $periodEnd] = PayrollPeriod::resolve($currentMonth, $currentYear);

        $batch = ImportBatch::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        if (! $batch) {
            return [
                'has_data'          => false,
                'attendance_rate'   => null,
                'total_late_hours'  => 0,
                'total_ot_hours'    => 0,
                'total_work_hours'  => 0,
                'avg_work_hours_per_day' => 0,
                'total_absent_days' => 0,
                'remote_days'       => 0,
                'onsite_days'       => 0,
                'present_days'      => 0,
                'total_records'     => 0,
                'batch'             => null,
            ];
        }

        $records = AttendanceRecord::whereBetween('date', [
            $periodStart->toDateString(),
            $periodEnd->toDateString(),
        ]);

        if ($actor !== null) {
            $employeeIds = $this->scopedEmployeeIds($actor);

            if (empty($employeeIds)) {
                return [
                    'has_data'          => false,
                    'attendance_rate'   => null,
                    'total_late_hours'  => 0,
                    'total_ot_hours'    => 0,
                    'total_work_hours'  => 0,
                    'avg_work_hours_per_day' => 0,
                    'total_absent_days' => 0,
                    'remote_days'       => 0,
                    'onsite_days'       => 0,
                    'present_days'      => 0,
                    'total_records'     => 0,
                    'batch'             => $batch,
                ];
            }

            $records->whereIn('employee_id', $employeeIds);
        }

        // استبعاد أيام الجمعة (DAYOFWEEK = 6 في MySQL) من حساب نسبة الحضور
        // نحسب فقط من أيام العمل (5 أيام في الأسبوع)
        $workingDaysRecords = (clone $records)->whereRaw('DAYOFWEEK(date) != 6')->count();
        $presentDays        = (clone $records)->whereRaw('DAYOFWEEK(date) != 6')->where('is_absent', false)->count();
        $absentDays         = (clone $records)->whereRaw('DAYOFWEEK(date) != 6')->where('is_absent', true)->count();
        $remoteDays         = (clone $records)
            ->whereRaw('DAYOFWEEK(date) != 6')
            ->where('is_absent', false)
            ->where('type', 'remote')
            ->count();
        $onsiteDays         = max($presentDays - $remoteDays, 0);

        $totalRecords  = $records->count(); // جميع السجلات (مع الجمعة)
        $totalLate     = (clone $records)->sum('late_minutes');
        $totalOT       = (clone $records)->sum('overtime_minutes');
        $totalWork     = (clone $records)->sum('work_minutes');

        // حساب نسبة الحضور من أيام العمل فقط (بدون الجمعة)
        $attendanceRate = $workingDaysRecords > 0
            ? round(($presentDays / $workingDaysRecords) * 100, 1)
            : 0;
        $avgWorkHoursPerDay = $presentDays > 0
            ? round(($totalWork / $presentDays) / 60, 2)
            : 0;

        return [
            'has_data'          => true,
            'attendance_rate'   => $attendanceRate,
            'total_late_hours'  => round($totalLate / 60, 1),
            'total_ot_hours'    => round($totalOT / 60, 1),
            'total_work_hours'  => round($totalWork / 60, 1),
            'avg_work_hours_per_day' => $avgWorkHoursPerDay,
            'total_absent_days' => $absentDays,
            'remote_days'       => $remoteDays,
            'onsite_days'       => $onsiteDays,
            'present_days'      => $presentDays,
            'total_records'     => $totalRecords,
            'batch'             => $batch,
        ];
    }

    private function getRecentBatches(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return ImportBatch::latest()
            ->limit($limit)
            ->get();
    }

    /**
     * أكثر 5 موظفين تأخيراً في الشهر الحالي
     */
    private function getTopLateEmployees(int $limit = 5, ?User $actor = null): \Illuminate\Support\Collection
    {
        $currentPayrollMonth = PayrollPeriod::monthForDate(Carbon::now());
        [$periodStart, $periodEnd] = PayrollPeriod::resolve($currentPayrollMonth['month'], $currentPayrollMonth['year']);

        $query = AttendanceRecord::whereBetween('date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->where('late_minutes', '>', 0)
            ->select('employee_id', DB::raw('SUM(late_minutes) as total_late'))
            ->groupBy('employee_id')
            ->orderByDesc('total_late')
            ->limit($limit);

        if ($actor !== null) {
            $employeeIds = $this->scopedEmployeeIds($actor);
            if (empty($employeeIds)) {
                return collect();
            }

            $query->whereIn('employee_id', $employeeIds);
        }

        return $query->with('employee.user.profile')
            ->get()
            ->map(function ($item) {
                return [
                    'employee'   => $item->employee,
                    'late_hours' => round($item->total_late / 60, 1),
                    'late_minutes' => $item->total_late,
                ];
            });
    }

    /**
     * أكثر 5 موظفين أوفرتايم في الشهر الحالي
     */
    private function getTopOTEmployees(int $limit = 5, ?User $actor = null): \Illuminate\Support\Collection
    {
        $currentPayrollMonth = PayrollPeriod::monthForDate(Carbon::now());
        [$periodStart, $periodEnd] = PayrollPeriod::resolve($currentPayrollMonth['month'], $currentPayrollMonth['year']);

        $query = AttendanceRecord::whereBetween('date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->where('overtime_minutes', '>', 0)
            ->select('employee_id', DB::raw('SUM(overtime_minutes) as total_ot'))
            ->groupBy('employee_id')
            ->orderByDesc('total_ot')
            ->limit($limit);

        if ($actor !== null) {
            $employeeIds = $this->scopedEmployeeIds($actor);
            if (empty($employeeIds)) {
                return collect();
            }

            $query->whereIn('employee_id', $employeeIds);
        }

        return $query->with('employee.user.profile')
            ->get()
            ->map(function ($item) {
                return [
                    'employee' => $item->employee,
                    'ot_hours' => round($item->total_ot / 60, 1),
                    'ot_minutes' => $item->total_ot,
                ];
            });
    }

    /**
     * أكثر الموظفين في ساعات العمل خلال الشهر الحالي
     */
    private function getTopWorkEmployees(int $limit = 5, ?User $actor = null): \Illuminate\Support\Collection
    {
        $currentPayrollMonth = PayrollPeriod::monthForDate(Carbon::now());
        [$periodStart, $periodEnd] = PayrollPeriod::resolve($currentPayrollMonth['month'], $currentPayrollMonth['year']);

        $query = AttendanceRecord::whereBetween('date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->where('is_absent', false)
            ->where('work_minutes', '>', 0)
            ->select('employee_id', DB::raw('SUM(work_minutes) as total_work'))
            ->groupBy('employee_id')
            ->orderByDesc('total_work')
            ->limit($limit);

        if ($actor !== null) {
            $employeeIds = $this->scopedEmployeeIds($actor);
            if (empty($employeeIds)) {
                return collect();
            }

            $query->whereIn('employee_id', $employeeIds);
        }

        return $query->with('employee.user.profile')
            ->get()
            ->map(function ($item) {
                return [
                    'employee'      => $item->employee,
                    'work_hours'    => round($item->total_work / 60, 1),
                    'work_minutes'  => (int) $item->total_work,
                ];
            });
    }

    private function scopedEmployeeIds(User $actor): array
    {
        return $this->departmentScopeService
            ->visibleEmployeeIdsQuery($actor)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
