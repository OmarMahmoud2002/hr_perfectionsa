<?php

namespace App\Services\EmployeeOfMonth;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeMonthAdminScore;
use App\Models\EmployeeMonthVote;
use App\Services\Payroll\PayrollPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeOfMonthMetricsService
{
    public function __construct(
        private readonly EmployeeTaskScoreService $taskScoreService,
    ) {}

    public function getEligibleEmployees(): Collection
    {
        return Employee::query()
            ->where('is_active', true)
            ->whereHas('user', fn ($q) => $q->where('role', 'employee'))
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    public function getMonthlyMetrics(int $month, int $year): array
    {
        $employees = $this->getEligibleEmployees();
        $employeeIds = $employees->pluck('id')->all();

        [$periodStart, $periodEnd] = PayrollPeriod::resolve($month, $year);

        $votesByEmployee = EmployeeMonthVote::query()
            ->selectRaw('voted_employee_id, COUNT(*) as votes_count')
            ->where('vote_month', $month)
            ->where('vote_year', $year)
            ->groupBy('voted_employee_id')
            ->pluck('votes_count', 'voted_employee_id');

        $totalValidVotes = (int) DB::table('employee_month_votes as v')
            ->join('users as u', 'u.id', '=', 'v.voter_user_id')
            ->where('v.vote_month', $month)
            ->where('v.vote_year', $year)
            ->where('u.role', 'employee')
            ->count();

        $votersCount = (int) DB::table('employee_month_votes as v')
            ->join('users as u', 'u.id', '=', 'v.voter_user_id')
            ->where('v.vote_month', $month)
            ->where('v.vote_year', $year)
            ->where('u.role', 'employee')
            ->distinct('v.voter_user_id')
            ->count('v.voter_user_id');

        $attendanceByEmployee = AttendanceRecord::query()
            ->selectRaw('employee_id, SUM(work_minutes) as total_work_minutes, SUM(late_minutes) as total_late_minutes, SUM(overtime_minutes) as total_overtime_minutes')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        $adminScoresByEmployee = EmployeeMonthAdminScore::query()
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('employee_id');

        $taskMetrics = $this->taskScoreService->getMonthlyTaskMetrics($month, $year, $employeeIds);
        /** @var Collection $taskByEmployee */
        $taskByEmployee = $taskMetrics['by_employee'];

        $rows = $employees->map(function (Employee $employee) use ($votesByEmployee, $attendanceByEmployee, $adminScoresByEmployee, $taskByEmployee) {
            $attendance = $attendanceByEmployee->get($employee->id);
            $adminScore = $adminScoresByEmployee->get($employee->id);
            $task = $taskByEmployee->get($employee->id);

            return [
                'employee_id' => $employee->id,
                'employee' => $employee,
                'votes_count' => (int) ($votesByEmployee[$employee->id] ?? 0),
                'work_minutes' => (int) ($attendance->total_work_minutes ?? 0),
                'late_minutes' => (int) ($attendance->total_late_minutes ?? 0),
                'overtime_minutes' => (int) ($attendance->total_overtime_minutes ?? 0),
                'admin_score' => $adminScore ? (float) $adminScore->score : null,
                'task_score_raw' => $task['task_score_raw'] ?? null,
                'assigned_tasks_count' => (int) ($task['assigned_tasks_count'] ?? 0),
                'evaluated_tasks_count' => (int) ($task['evaluated_tasks_count'] ?? 0),
            ];
        });

        return [
            'month' => $month,
            'year' => $year,
            'total_valid_votes' => $totalValidVotes,
            'voters_count' => $votersCount,
            'task_period_totals' => $taskMetrics['period_totals'],
            'rows' => $rows,
        ];
    }
}
