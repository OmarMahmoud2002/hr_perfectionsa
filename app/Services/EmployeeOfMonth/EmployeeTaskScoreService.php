<?php

namespace App\Services\EmployeeOfMonth;

use App\Models\EmployeeMonthTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeTaskScoreService
{
    public function getMonthlyTaskMetrics(int $month, int $year, array $employeeIds): array
    {
        $employeeIds = collect($employeeIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (count($employeeIds) === 0) {
            return [
                'by_employee' => collect(),
                'period_totals' => [
                    'tasks_count' => 0,
                    'evaluated_tasks_count' => 0,
                    'coverage_ratio' => 0.0,
                ],
            ];
        }

        $taskIds = EmployeeMonthTask::query()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->pluck('id');

        if ($taskIds->isEmpty()) {
            return [
                'by_employee' => collect($employeeIds)
                    ->mapWithKeys(fn ($employeeId) => [$employeeId => $this->emptyEmployeeMetric((int) $employeeId)]),
                'period_totals' => [
                    'tasks_count' => 0,
                    'evaluated_tasks_count' => 0,
                    'coverage_ratio' => 0.0,
                ],
            ];
        }

        $rows = DB::table('employee_month_task_assignments as a')
            ->join('employee_month_tasks as t', 't.id', '=', 'a.task_id')
            ->leftJoin('employee_month_task_evaluations as e', 'e.task_id', '=', 't.id')
            ->where('t.period_month', $month)
            ->where('t.period_year', $year)
            ->whereIn('a.employee_id', $employeeIds)
            ->groupBy('a.employee_id')
            ->selectRaw('a.employee_id as employee_id')
            ->selectRaw('COUNT(*) as assigned_tasks_count')
            ->selectRaw('SUM(CASE WHEN e.id IS NOT NULL THEN 1 ELSE 0 END) as evaluated_tasks_count')
            ->selectRaw('AVG(CASE WHEN e.id IS NOT NULL THEN e.score END) as task_score_raw')
            ->get();

        $byEmployee = collect($employeeIds)
            ->mapWithKeys(fn ($employeeId) => [(int) $employeeId => $this->emptyEmployeeMetric((int) $employeeId)]);

        foreach ($rows as $row) {
            $employeeId = (int) $row->employee_id;

            $byEmployee[$employeeId] = [
                'employee_id' => $employeeId,
                'assigned_tasks_count' => (int) $row->assigned_tasks_count,
                'evaluated_tasks_count' => (int) $row->evaluated_tasks_count,
                'task_score_raw' => $row->task_score_raw !== null ? (float) $row->task_score_raw : null,
            ];
        }

        $evaluatedTasksCount = DB::table('employee_month_task_evaluations')
            ->whereIn('task_id', $taskIds->all())
            ->count();

        $tasksCount = $taskIds->count();

        return [
            'by_employee' => $byEmployee,
            'period_totals' => [
                'tasks_count' => $tasksCount,
                'evaluated_tasks_count' => (int) $evaluatedTasksCount,
                'coverage_ratio' => $tasksCount > 0 ? round(((int) $evaluatedTasksCount / $tasksCount) * 100, 2) : 0.0,
            ],
        ];
    }

    private function emptyEmployeeMetric(int $employeeId): array
    {
        return [
            'employee_id' => $employeeId,
            'assigned_tasks_count' => 0,
            'evaluated_tasks_count' => 0,
            'task_score_raw' => null,
        ];
    }
}
