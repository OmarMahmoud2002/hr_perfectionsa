<?php

namespace App\Services\EmployeeOfMonth;

use App\Services\Setting\SettingService;
use Illuminate\Support\Collection;

class EmployeeOfMonthScoringService
{
    private const DEFAULT_WEIGHTS = [
        'tasks' => 0.40,
        'punctuality' => 0.15,
        'work_hours' => 0.20,
        'vote' => 0.25,
    ];

    private const SCORE_FLOOR = 20.0;
    private const SCORE_CEILING = 100.0;
    private const NEUTRAL_SCORE = 60.0;

    private const LEGACY_WEIGHTS = [
        // Kept for backward compatibility with older breakdown readers.
        'admin' => 0.0,
        'work_hours' => 0.20,
        'overtime' => 0.0,
    ];

    public function __construct(
        private readonly EmployeeOfMonthMetricsService $metricsService,
        private readonly SettingService $settingService,
    ) {}

    public function calculateForMonth(int $month, int $year, ?array $metricsData = null): array
    {
        $metricsData ??= $this->metricsService->getMonthlyMetrics($month, $year);

        /** @var Collection $rows */
        $rows = $metricsData['rows'];

        $weights = $this->resolveWeights();
        $enabledCriteria = $this->resolveEnabledCriteria(array_keys($weights));

        $voteByEmployee = $rows->mapWithKeys(fn (array $row) => [(int) $row['employee_id'] => (float) $row['votes_count']]);
        $workByEmployee = $rows->mapWithKeys(fn (array $row) => [(int) $row['employee_id'] => (float) $row['work_minutes']]);
        $lateByEmployee = $rows->mapWithKeys(fn (array $row) => [(int) $row['employee_id'] => (float) $row['late_minutes']]);
        $taskByEmployee = $rows->mapWithKeys(fn (array $row) => [(int) $row['employee_id'] => isset($row['task_score_raw']) ? (float) $row['task_score_raw'] : null]);

        $taskScores = $this->normalizeByPercentiles($taskByEmployee, higherIsBetter: true);
        $voteScores = $this->normalizeByPercentiles($voteByEmployee, higherIsBetter: true);
        $workScores = $this->normalizeByPercentiles($workByEmployee, higherIsBetter: true);
        $punctualityScores = $this->normalizeByPercentiles($lateByEmployee, higherIsBetter: false);

        $scored = $rows->map(function (array $row) use ($weights, $enabledCriteria, $taskScores, $voteScores, $workScores, $punctualityScores) {
            $employeeId = (int) $row['employee_id'];

            $components = [
                'tasks' => (float) $taskScores->get($employeeId, self::NEUTRAL_SCORE),
                'vote' => (float) $voteScores->get($employeeId, self::NEUTRAL_SCORE),
                'work_hours' => (float) $workScores->get($employeeId, self::NEUTRAL_SCORE),
                'punctuality' => (float) $punctualityScores->get($employeeId, self::NEUTRAL_SCORE),
                // Legacy compatibility keys for old UI/exports before Stage D.
                'admin' => 0.0,
                'overtime' => 0.0,
            ];

            $finalScore = 0.0;
            foreach ($enabledCriteria as $criterion) {
                $finalScore += ($weights[$criterion] ?? 0.0) * ($components[$criterion] ?? 0.0);
            }

            return [
                'employee_id' => $row['employee_id'],
                'employee' => $row['employee'],
                'final_score' => round($finalScore, 2),
                'breakdown' => [
                    'task_score' => round($components['tasks'], 2),
                    'vote_score' => round($components['vote'], 2),
                    'admin_score' => round($components['admin'], 2),
                    'work_hours_score' => round($components['work_hours'], 2),
                    'punctuality_score' => round($components['punctuality'], 2),
                    'overtime_score' => round($components['overtime'], 2),
                    'weights_applied' => $weights,
                    'criteria_enabled' => array_values($enabledCriteria),
                    'raw_inputs' => [
                        'votes_count' => (int) $row['votes_count'],
                        'task_score_raw' => $row['task_score_raw'] === null ? null : (float) $row['task_score_raw'],
                        'assigned_tasks_count' => (int) ($row['assigned_tasks_count'] ?? 0),
                        'evaluated_tasks_count' => (int) ($row['evaluated_tasks_count'] ?? 0),
                        'admin_score' => $row['admin_score'] === null ? null : (float) $row['admin_score'],
                        'work_minutes' => (int) $row['work_minutes'],
                        'late_minutes' => (int) $row['late_minutes'],
                        'overtime_minutes' => (int) $row['overtime_minutes'],
                    ],
                ],
            ];
        })->sortByDesc('final_score')->values();

        return [
            'month' => $month,
            'year' => $year,
            'formula_version' => (string) $this->settingService->get('employee_of_month.formula_version', 'v2_tasks'),
            'weights' => $weights,
            'scored_rows' => $scored,
        ];
    }

    private function resolveWeights(): array
    {
        $weights = self::DEFAULT_WEIGHTS;

        foreach (array_keys(self::DEFAULT_WEIGHTS + self::LEGACY_WEIGHTS) as $key) {
            $default = self::DEFAULT_WEIGHTS[$key] ?? self::LEGACY_WEIGHTS[$key] ?? 0.0;
            $weights[$key] = (float) $this->settingService->get('employee_of_month.weights.' . $key, $default);
        }

        foreach (self::LEGACY_WEIGHTS as $key => $value) {
            if (! array_key_exists($key, $weights)) {
                $weights[$key] = $value;
            }
        }

        return $weights;
    }

    private function resolveEnabledCriteria(array $defaultCriteria): array
    {
        $raw = $this->settingService->get('employee_of_month.criteria');
        if (! is_string($raw) || trim($raw) === '') {
            return $defaultCriteria;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $defaultCriteria;
        }

        $enabled = collect($decoded)
            ->filter(fn ($item) => is_array($item) && ($item['enabled'] ?? false) === true)
            ->pluck('key')
            ->filter(fn ($key) => is_string($key) && in_array($key, $defaultCriteria, true))
            ->values()
            ->all();

        return count($enabled) > 0 ? $enabled : $defaultCriteria;
    }

    private function clampScore(float $value): float
    {
        return max(0.0, min(100.0, $value));
    }

    private function normalizeByPercentiles(Collection $valuesByEmployee, bool $higherIsBetter): Collection
    {
        $numericValues = $valuesByEmployee
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($numericValues->isEmpty()) {
            return $valuesByEmployee->map(fn () => self::NEUTRAL_SCORE);
        }

        $p10 = $this->percentile($numericValues, 0.10);
        $p90 = $this->percentile($numericValues, 0.90);
        $range = $p90 - $p10;

        if (abs($range) < 0.000001) {
            return $valuesByEmployee->map(fn () => self::NEUTRAL_SCORE);
        }

        return $valuesByEmployee->map(function ($value) use ($higherIsBetter, $p10, $p90, $range) {
            if ($value === null) {
                return self::NEUTRAL_SCORE;
            }

            $raw = (float) $value;
            $clipped = max($p10, min($p90, $raw));
            $norm = $higherIsBetter
                ? ($clipped - $p10) / $range
                : ($p90 - $clipped) / $range;

            $score = self::SCORE_FLOOR + ((self::SCORE_CEILING - self::SCORE_FLOOR) * $this->clampScore($norm * 100) / 100);

            return round(max(self::SCORE_FLOOR, min(self::SCORE_CEILING, $score)), 2);
        });
    }

    private function percentile(Collection $values, float $percentile): float
    {
        $sorted = $values
            ->map(fn ($value) => (float) $value)
            ->sort()
            ->values();

        if ($sorted->count() === 1) {
            return (float) $sorted->first();
        }

        $index = ($sorted->count() - 1) * max(0.0, min(1.0, $percentile));
        $lowerIndex = (int) floor($index);
        $upperIndex = (int) ceil($index);

        if ($lowerIndex === $upperIndex) {
            return (float) $sorted->get($lowerIndex);
        }

        $weight = $index - $lowerIndex;
        $lower = (float) $sorted->get($lowerIndex);
        $upper = (float) $sorted->get($upperIndex);

        return $lower + (($upper - $lower) * $weight);
    }
}
