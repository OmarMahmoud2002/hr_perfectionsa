<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TasksEvaluationsExport implements FromCollection, WithStyles, WithTitle, WithColumnWidths
{
    public function __construct(
        private readonly Collection $tasks,
        private readonly int $month,
        private readonly int $year,
    ) {}

    public function title(): string
    {
        return "tasks_eval_{$this->month}_{$this->year}";
    }

    public function collection(): Collection
    {
        $titleRow = collect([array_fill(0, 11, '')]);

        $headerRow = collect([[
            '#',
            'Task Title',
            'Description',
            'Assigned Employees',
            'Assigned Count',
            'Evaluator',
            'Score',
            'Note',
            'Status',
            'Last Update',
            'Active',
        ]]);

        $dataRows = $this->tasks->values()->map(function ($task, int $index) {
            $assignedEmployees = $task->employees
                ->map(fn ($employee) => $employee->name . ' (' . $employee->ac_no . ')')
                ->implode(' | ');

            return [
                $index + 1,
                (string) $task->title,
                (string) ($task->description ?? ''),
                (string) $assignedEmployees,
                (int) $task->employees->count(),
                (string) ($task->evaluation?->evaluator?->name ?? ''),
                $task->evaluation?->score !== null ? (int) $task->evaluation->score : '',
                (string) ($task->evaluation?->note ?? ''),
                $task->evaluation ? 'Evaluated' : 'Pending',
                (string) ($task->evaluation?->updated_at?->format('Y-m-d H:i') ?? ''),
                $task->is_active ? 'Active' : 'Inactive',
            ];
        });

        return $titleRow->concat($headerRow)->concat($dataRows);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 24,
            'C' => 26,
            'D' => 45,
            'E' => 13,
            'F' => 18,
            'G' => 10,
            'H' => 26,
            'I' => 14,
            'J' => 18,
            'K' => 10,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $monthLabel = \Carbon\Carbon::create($this->year, $this->month, 1)->locale('ar')->isoFormat('MMMM YYYY');
        $lastRow = $this->tasks->count() + 2;

        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', "Tasks & Evaluations - {$monthLabel}");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2F7C77']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->getStyle('A2:K2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF1E293B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2E8F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF94A3B8']],
            ],
        ]);

        if ($lastRow > 2) {
            $sheet->getStyle("A3:K{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $sheet->getStyle("B3:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("H3:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("G3:G{$lastRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FF047857']],
            ]);

            foreach (range(3, $lastRow) as $row) {
                if ($row % 2 === 1) {
                    $sheet->getStyle("A{$row}:K{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFF8FAFC');
                }

                $status = (string) $sheet->getCell("I{$row}")->getValue();
                if ($status === 'Evaluated') {
                    $sheet->getStyle("I{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FF047857']],
                    ]);
                } elseif ($status === 'Pending') {
                    $sheet->getStyle("I{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FFB45309']],
                    ]);
                }
            }
        }

        return [];
    }
}
