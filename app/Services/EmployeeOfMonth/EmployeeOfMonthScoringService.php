<?php

namespace App\Services\EmployeeOfMonth;

use App\Services\Setting\SettingService;
use Illuminate\Support\Collection;

class EmployeeOfMonthScoringService
{
    private const TASKS_MAX_POINTS = 40.0;
    private const VOTE_MAX_POINTS = 25.0;
    private const WORK_HOURS_MAX_POINTS = 20.0;
    private const PUNCTUALITY_MAX_POINTS = 15.0;

    public function __construct(
        private readonly EmployeeOfMonthMetricsService $metricsService,
        private readonly SettingService $settingService,
    ) {}

    public function calculateForMonth(int $month, int $year, ?array $metricsData = null): array
    {
        $metricsData ??= $this->metricsService->getMonthlyMetrics($month, $year);

        /** @var Collection $rows */
        $rows = $metricsData['rows'];

        $totalValidVotes = (int) ($metricsData['total_valid_votes'] ?? 0);
        $maxWorkMinutes = (float) $rows->max(fn (array $row) => (int) ($row['work_minutes'] ?? 0));
        $maxLateMinutes = (float) $rows->max(fn (array $row) => (int) ($row['late_minutes'] ?? 0));

        $scored = $rows->map(function (array $row) use ($totalValidVotes, $maxWorkMinutes, $maxLateMinutes) {
            $votesCount = (int) ($row['votes_count'] ?? 0);
            $workMinutes = (int) ($row['work_minutes'] ?? 0);
            $lateMinutes = (int) ($row['late_minutes'] ?? 0);
            $taskScoreRaw = $row['task_score_raw'] === null ? null : (float) $row['task_score_raw'];

            $taskPoints = $taskScoreRaw === null
                ? 0.0
                : $this->clamp(($taskScoreRaw / 10) * self::TASKS_MAX_POINTS, 0.0, self::TASKS_MAX_POINTS);

            $votePoints = $totalValidVotes > 0
                ? $this->clamp(($votesCount / $totalValidVotes) * self::VOTE_MAX_POINTS, 0.0, self::VOTE_MAX_POINTS)
                : 0.0;

            $workHoursPoints = $maxWorkMinutes > 0
                ? $this->clamp(($workMinutes / $maxWorkMinutes) * self::WORK_HOURS_MAX_POINTS, 0.0, self::WORK_HOURS_MAX_POINTS)
                : 0.0;

            $punctualityPoints = $maxLateMinutes <= 0
                ? self::PUNCTUALITY_MAX_POINTS
                : $this->clamp((1 - ($lateMinutes / $maxLateMinutes)) * self::PUNCTUALITY_MAX_POINTS, 0.0, self::PUNCTUALITY_MAX_POINTS);

            $finalScore = $taskPoints + $votePoints + $workHoursPoints + $punctualityPoints;

            return [
                'employee_id' => $row['employee_id'],
                'employee' => $row['employee'],
                'final_score' => round($finalScore, 2),
                'breakdown' => [
                    'task_points' => round($taskPoints, 2),
                    'vote_points' => round($votePoints, 2),
                    'work_hours_points' => round($workHoursPoints, 2),
                    'punctuality_points' => round($punctualityPoints, 2),
                    'final_points' => round($finalScore, 2),
                    // Legacy keys kept for backward compatibility with existing UI/exports.
                    'task_score' => round($taskPoints, 2),
                    'vote_score' => round($votePoints, 2),
                    'admin_score' => 0.0,
                    'work_hours_score' => round($workHoursPoints, 2),
                    'punctuality_score' => round($punctualityPoints, 2),
                    'overtime_score' => 0.0,
                    'points_caps' => [
                        'tasks' => self::TASKS_MAX_POINTS,
                        'vote' => self::VOTE_MAX_POINTS,
                        'work_hours' => self::WORK_HOURS_MAX_POINTS,
                        'punctuality' => self::PUNCTUALITY_MAX_POINTS,
                    ],
                    'weights_applied' => [
                        'tasks' => 0.40,
                        'vote' => 0.25,
                        'work_hours' => 0.20,
                        'punctuality' => 0.15,
                    ],
                    'criteria_enabled' => ['tasks', 'vote', 'work_hours', 'punctuality'],
                    'raw_inputs' => [
                        'votes_count' => $votesCount,
                        'task_score_raw' => $taskScoreRaw,
                        'assigned_tasks_count' => (int) ($row['assigned_tasks_count'] ?? 0),
                        'evaluated_tasks_count' => (int) ($row['evaluated_tasks_count'] ?? 0),
                        'admin_score' => $row['admin_score'] === null ? null : (float) $row['admin_score'],
                        'work_minutes' => $workMinutes,
                        'late_minutes' => $lateMinutes,
                        'overtime_minutes' => (int) $row['overtime_minutes'],
                    ],
                ],
            ];
        })->sort(function (array $a, array $b) {
            $finalCompare = ($b['final_score'] <=> $a['final_score']);
            if ($finalCompare !== 0) {
                return $finalCompare;
            }

            // Tie-breaker rule: tasks points decide the winner.
            return (($b['breakdown']['task_points'] ?? 0) <=> ($a['breakdown']['task_points'] ?? 0));
        })->values();

        return [
            'month' => $month,
            'year' => $year,
            'formula_version' => (string) $this->settingService->get('employee_of_month.formula_version', 'v3_weighted_points'),
            'weights' => [
                'tasks' => 0.40,
                'vote' => 0.25,
                'work_hours' => 0.20,
                'punctuality' => 0.15,
            ],
            'points_caps' => [
                'tasks' => self::TASKS_MAX_POINTS,
                'vote' => self::VOTE_MAX_POINTS,
                'work_hours' => self::WORK_HOURS_MAX_POINTS,
                'punctuality' => self::PUNCTUALITY_MAX_POINTS,
            ],
            'scored_rows' => $scored,
        ];
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
