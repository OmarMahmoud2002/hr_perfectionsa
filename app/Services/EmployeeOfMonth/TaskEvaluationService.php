<?php

namespace App\Services\EmployeeOfMonth;

use App\Models\EmployeeMonthTask;
use App\Models\EmployeeMonthTaskEvaluation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TaskEvaluationService
{
    public function getTasksForEvaluator(User $evaluator, int $month, int $year, ?string $taskDate = null, ?string $status = null): Collection
    {
        $this->assertEvaluatorRole($evaluator);

        $query = EmployeeMonthTask::query()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->where('is_active', true)
            ->with([
                'evaluation' => fn ($q) => $q
                    ->select(['id', 'task_id', 'evaluator_user_id', 'score', 'note', 'updated_at']),
                'employees:id,name',
                'assignments.employee:id,name',
                'attachments',
                'links',
            ])
            ->where(function ($query) use ($evaluator) {
                $query->whereDoesntHave('evaluation')
                    ->orWhereHas('evaluation', fn ($q) => $q->where('evaluator_user_id', $evaluator->id));
            })
            ->orderByDesc('id');

        if (! empty($taskDate)) {
            $query->whereDate('task_date', $taskDate);
        }

        if ($status === 'evaluated') {
            $query->whereHas('evaluation', fn ($q) => $q->where('evaluator_user_id', $evaluator->id));
        } elseif ($status === 'not_evaluated') {
            $query->whereDoesntHave('evaluation');
        }

        return $query->get(['id', 'title', 'description', 'task_date', 'task_end_date', 'period_month', 'period_year']);
    }

    public function upsertEvaluation(User $evaluator, EmployeeMonthTask $task, float $score, ?string $note = null): EmployeeMonthTaskEvaluation
    {
        $this->assertEvaluatorRole($evaluator);

        if ($score < 1 || $score > 10) {
            throw new RuntimeException('Invalid score value. Score must be between 1 and 10.');
        }

        return DB::transaction(function () use ($evaluator, $task, $score, $note) {
            $existing = EmployeeMonthTaskEvaluation::query()
                ->where('task_id', $task->id)
                ->lockForUpdate()
                ->first();

            if ($existing && (int) $existing->evaluator_user_id !== (int) $evaluator->id) {
                throw new RuntimeException('This task is already evaluated by another evaluator user.');
            }

            if ($existing) {
                $existing->update([
                    'score' => $score,
                    'note' => $note,
                ]);

                return $existing->fresh();
            }

            return EmployeeMonthTaskEvaluation::query()->create([
                'task_id' => $task->id,
                'evaluator_user_id' => $evaluator->id,
                'score' => $score,
                'note' => $note,
            ]);
        });
    }

    private function assertEvaluatorRole(User $evaluator): void
    {
        if (! $evaluator->isEvaluatorUser()) {
            throw new RuntimeException('Only evaluator users can access task evaluation endpoints.');
        }
    }
}
