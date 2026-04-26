<?php

namespace App\Services\EmployeeOfMonth;

use App\Models\EmployeeOfMonthPublication;
use App\Models\EmployeeOfMonthResult;
use App\Services\Notifications\EmailNotificationService;
use Illuminate\Support\Facades\DB;

class EmployeeOfMonthFinalizationService
{
    public function __construct(
        private readonly EmployeeOfMonthScoringService $scoringService,
        private readonly EmailNotificationService $emailNotificationService,
    ) {}

    public function finalizeMonth(int $month, int $year, ?int $publishedByUserId = null): array
    {
        $scoring = $this->scoringService->calculateForMonth($month, $year);
        $rows = $scoring['scored_rows'];
        $generatedAt = now();

        DB::transaction(function () use ($rows, $scoring, $month, $year, $generatedAt, $publishedByUserId) {
            $payload = $rows->map(function (array $row) use ($scoring, $month, $year, $generatedAt) {
                return [
                    'employee_id' => $row['employee_id'],
                    'month' => $month,
                    'year' => $year,
                    'final_score' => $row['final_score'],
                    'breakdown' => json_encode($row['breakdown'], JSON_THROW_ON_ERROR),
                    'formula_version' => $scoring['formula_version'],
                    'generated_at' => $generatedAt,
                    'created_at' => $generatedAt,
                    'updated_at' => $generatedAt,
                ];
            })->all();

            if (count($payload) === 0) {
                return;
            }

            EmployeeOfMonthResult::query()->upsert(
                $payload,
                ['employee_id', 'month', 'year'],
                ['final_score', 'breakdown', 'formula_version', 'generated_at', 'updated_at']
            );

            EmployeeOfMonthPublication::query()->updateOrCreate(
                [
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'published_at' => $generatedAt,
                    'published_by_user_id' => $publishedByUserId,
                ]
            );
        });

        $winnerEmployeeId = null;
        $winnerRow = $rows->first();
        if (is_array($winnerRow) && isset($winnerRow['employee_id'])) {
            $winnerEmployeeId = (int) $winnerRow['employee_id'];
        }

        $this->emailNotificationService->notifyEmployeeOfMonthPublished($month, $year, $winnerEmployeeId);

        return [
            'month' => $month,
            'year' => $year,
            'formula_version' => $scoring['formula_version'],
            'rows_count' => $rows->count(),
            'rows' => $rows,
        ];
    }
}
