<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EvaluatorTasksExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly Collection $tasks,
    ) {}

    public function headings(): array
    {
        return [
            'Task Title',
            'Task Date',
            'Description',
            'My Score',
            'My Note',
            'Status',
        ];
    }

    public function collection(): Collection
    {
        return $this->tasks->map(function ($task) {
            return [
                (string) $task->title,
                (string) ($task->task_date?->format('Y-m-d') ?? ''),
                (string) ($task->description ?? ''),
                $task->evaluation?->score !== null ? (int) $task->evaluation->score : '',
                (string) ($task->evaluation?->note ?? ''),
                $task->evaluation ? 'Evaluated' : 'Pending',
            ];
        });
    }
}
