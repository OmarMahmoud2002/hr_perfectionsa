<?php

namespace App\Services\EmployeeOfMonth;

use App\Models\EmployeeMonthTask;
use App\Models\User;
use App\Services\Payroll\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskManagementService
{
    public function createTask(array $data, array $employeeIds, User $creator): EmployeeMonthTask
    {
        [$periodMonth, $periodYear] = $this->resolvePeriod($data);
        [$periodStart, $periodEnd] = PayrollPeriod::resolve($periodMonth, $periodYear);

        return DB::transaction(function () use ($data, $employeeIds, $creator, $periodMonth, $periodYear, $periodStart, $periodEnd) {
            $task = EmployeeMonthTask::query()->create([
                'title' => (string) $data['title'],
                'description' => $data['description'] ?? null,
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'period_start_date' => $periodStart->toDateString(),
                'period_end_date' => $periodEnd->toDateString(),
                'task_date' => isset($data['task_date']) ? Carbon::parse((string) $data['task_date'])->toDateString() : Carbon::now()->toDateString(),
                'created_by' => $creator->id,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $task->employees()->sync($this->sanitizeEmployeeIds($employeeIds));

            return $task->fresh(['assignments', 'employees', 'evaluation']);
        });
    }

    public function updateTask(EmployeeMonthTask $task, array $data, ?array $employeeIds = null): EmployeeMonthTask
    {
        [$periodMonth, $periodYear] = $this->resolvePeriod($data, $task);
        [$periodStart, $periodEnd] = PayrollPeriod::resolve($periodMonth, $periodYear);

        return DB::transaction(function () use ($task, $data, $employeeIds, $periodMonth, $periodYear, $periodStart, $periodEnd) {
            $task->update([
                'title' => (string) ($data['title'] ?? $task->title),
                'description' => array_key_exists('description', $data) ? $data['description'] : $task->description,
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'period_start_date' => $periodStart->toDateString(),
                'period_end_date' => $periodEnd->toDateString(),
                'task_date' => isset($data['task_date']) ? Carbon::parse((string) $data['task_date'])->toDateString() : ($task->task_date?->toDateString() ?? Carbon::now()->toDateString()),
                'is_active' => (bool) ($data['is_active'] ?? $task->is_active),
            ]);

            if ($employeeIds !== null) {
                $task->employees()->sync($this->sanitizeEmployeeIds($employeeIds));
            }

            return $task->fresh(['assignments', 'employees', 'evaluation']);
        });
    }

    public function deactivateTask(EmployeeMonthTask $task): EmployeeMonthTask
    {
        $task->update(['is_active' => false]);

        return $task->fresh(['assignments', 'employees', 'evaluation']);
    }

    public function getTasksForPeriod(int $month, int $year): Collection
    {
        return EmployeeMonthTask::query()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->withCount('assignments')
            ->with(['evaluation', 'creator'])
            ->orderByDesc('id')
            ->get();
    }

    private function resolvePeriod(array $data, ?EmployeeMonthTask $task = null): array
    {
        if (isset($data['period_month'], $data['period_year'])) {
            return [(int) $data['period_month'], (int) $data['period_year']];
        }

        if ($task !== null) {
            return [(int) $task->period_month, (int) $task->period_year];
        }

        $period = PayrollPeriod::monthForDate(Carbon::now());

        return [(int) $period['month'], (int) $period['year']];
    }

    private function sanitizeEmployeeIds(array $employeeIds): array
    {
        return collect($employeeIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
