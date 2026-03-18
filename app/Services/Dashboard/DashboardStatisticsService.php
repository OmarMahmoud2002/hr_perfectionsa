<?php

namespace App\Services\Dashboard;

use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\AttendanceRecord;
use App\Models\PayrollReport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatisticsService
{
    /**
     * مدة الـ Cache بالثواني (5 دقائق)
     */
    private const CACHE_TTL = 300;

    /**
     * جلب جميع إحصائيات لوحة التحكم
     */
    public function getStats(): array
    {
        return Cache::remember('dashboard_stats', self::CACHE_TTL, function () {
            return [
                'total_employees'    => $this->getTotalEmployees(),
                'active_employees'   => $this->getActiveEmployees(),
                'last_batch'         => $this->getLastBatch(),
                'current_month_stats'=> $this->getCurrentMonthStats(),
                'recent_batches'     => $this->getRecentBatches(),
                'top_late_employees' => $this->getTopLateEmployees(),
                'top_ot_employees'   => $this->getTopOTEmployees(),
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

    private function getTotalEmployees(): int
    {
        return Employee::count();
    }

    private function getActiveEmployees(): int
    {
        return Employee::active()->count();
    }

    private function getLastBatch(): ?ImportBatch
    {
        return ImportBatch::with('uploader')
            ->latest()
            ->first();
    }

    private function getCurrentMonthStats(): array
    {
        $currentMonth = now()->month;
        $currentYear  = now()->year;

        $batch = ImportBatch::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('status', 'completed')
            ->first();

        if (! $batch) {
            return [
                'has_data'          => false,
                'attendance_rate'   => null,
                'total_late_hours'  => 0,
                'total_ot_hours'    => 0,
                'total_absent_days' => 0,
                'present_days'      => 0,
                'total_records'     => 0,
                'batch'             => null,
            ];
        }

        $records = AttendanceRecord::where('import_batch_id', $batch->id);

        // استبعاد أيام الجمعة (DAYOFWEEK = 6 في MySQL) من حساب نسبة الحضور
        // نحسب فقط من أيام العمل (5 أيام في الأسبوع)
        $workingDaysRecords = (clone $records)->whereRaw('DAYOFWEEK(date) != 6')->count();
        $presentDays        = (clone $records)->whereRaw('DAYOFWEEK(date) != 6')->where('is_absent', false)->count();
        $absentDays         = (clone $records)->whereRaw('DAYOFWEEK(date) != 6')->where('is_absent', true)->count();

        $totalRecords  = $records->count(); // جميع السجلات (مع الجمعة)
        $totalLate     = (clone $records)->sum('late_minutes');
        $totalOT       = (clone $records)->sum('overtime_minutes');

        // حساب نسبة الحضور من أيام العمل فقط (بدون الجمعة)
        $attendanceRate = $workingDaysRecords > 0
            ? round(($presentDays / $workingDaysRecords) * 100, 1)
            : 0;

        return [
            'has_data'          => true,
            'attendance_rate'   => $attendanceRate,
            'total_late_hours'  => round($totalLate / 60, 1),
            'total_ot_hours'    => round($totalOT / 60, 1),
            'total_absent_days' => $absentDays,
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
    private function getTopLateEmployees(int $limit = 5): \Illuminate\Support\Collection
    {
        $currentMonth = now()->month;
        $currentYear  = now()->year;

        $batch = ImportBatch::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('status', 'completed')
            ->first();

        if (! $batch) {
            return collect();
        }

        return AttendanceRecord::where('import_batch_id', $batch->id)
            ->where('late_minutes', '>', 0)
            ->select('employee_id', DB::raw('SUM(late_minutes) as total_late'))
            ->groupBy('employee_id')
            ->orderByDesc('total_late')
            ->limit($limit)
            ->with('employee:id,name,ac_no')
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
    private function getTopOTEmployees(int $limit = 5): \Illuminate\Support\Collection
    {
        $currentMonth = now()->month;
        $currentYear  = now()->year;

        $batch = ImportBatch::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('status', 'completed')
            ->first();

        if (! $batch) {
            return collect();
        }

        return AttendanceRecord::where('import_batch_id', $batch->id)
            ->where('overtime_minutes', '>', 0)
            ->select('employee_id', DB::raw('SUM(overtime_minutes) as total_ot'))
            ->groupBy('employee_id')
            ->orderByDesc('total_ot')
            ->limit($limit)
            ->with('employee:id,name,ac_no')
            ->get()
            ->map(function ($item) {
                return [
                    'employee' => $item->employee,
                    'ot_hours' => round($item->total_ot / 60, 1),
                    'ot_minutes' => $item->total_ot,
                ];
            });
    }
}
