<?php

namespace App\Services\EmployeeOfMonth;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeOfMonthResult;
use App\Services\Payroll\PayrollPeriod;
use Illuminate\Support\Collection;

class BestManagerOfMonthService
{
    public function resolveForMonth(int $month, int $year): ?array
    {
        $topRows = EmployeeOfMonthResult::query()
            ->with('employee')
            ->where('month', $month)
            ->where('year', $year)
            ->where('final_score', '>=', EmployeeOfMonthScoringService::MIN_RANKING_SCORE)
            ->orderByDesc('final_score')
            ->limit(EmployeeOfMonthScoringService::WINNERS_COUNT)
            ->get();

        if ($topRows->count() < 3) {
            return null;
        }

        $byDepartment = $topRows
            ->filter(fn (EmployeeOfMonthResult $row) => (int) ($row->employee?->department_id ?? 0) > 0)
            ->groupBy(fn (EmployeeOfMonthResult $row) => (int) $row->employee->department_id)
            ->filter(fn (Collection $rows) => $rows->count() >= 3);

        if ($byDepartment->isEmpty()) {
            return null;
        }

        [$periodStart, $periodEnd] = PayrollPeriod::resolve($month, $year);

        $departmentCandidates = $byDepartment
            ->map(function (Collection $rows, int $departmentId) use ($periodStart, $periodEnd) {
                $department = Department::query()
                    ->with('managerEmployee.user.profile')
                    ->find($departmentId);

                if (! $department || ! $department->managerEmployee) {
                    return null;
                }

                $winnerEmployeeIds = $rows
                    ->pluck('employee_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $totalLateMinutes = (int) AttendanceRecord::query()
                    ->whereIn('employee_id', $winnerEmployeeIds)
                    ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->sum('late_minutes');

                $avgFinalScore = round((float) $rows->avg('final_score'), 4);
                $managerAssignedAt = $department->updated_at ?? $department->created_at;

                return [
                    'department' => $department,
                    'manager' => $department->managerEmployee,
                    'winners_count' => $rows->count(),
                    'avg_final_score' => $avgFinalScore,
                    'total_late_minutes' => $totalLateMinutes,
                    'manager_assigned_at' => $managerAssignedAt,
                ];
            })
            ->filter()
            ->values();

        if ($departmentCandidates->isEmpty()) {
            return null;
        }

        return $departmentCandidates
            ->sort(function (array $a, array $b) {
                $avgCompare = $b['avg_final_score'] <=> $a['avg_final_score'];
                if ($avgCompare !== 0) {
                    return $avgCompare;
                }

                $lateCompare = $a['total_late_minutes'] <=> $b['total_late_minutes'];
                if ($lateCompare !== 0) {
                    return $lateCompare;
                }

                $aTs = optional($a['manager_assigned_at'])->getTimestamp() ?? PHP_INT_MAX;
                $bTs = optional($b['manager_assigned_at'])->getTimestamp() ?? PHP_INT_MAX;

                return $aTs <=> $bTs;
            })
            ->first();
    }
}
