<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeOfMonthRankingExport implements FromCollection, WithStyles, WithTitle, WithColumnWidths
{
    public function __construct(
        private readonly array $scoring,
        private readonly int $month,
        private readonly int $year,
    ) {}

    public function title(): string
    {
        return "employee_of_month_{$this->month}_{$this->year}";
    }

    public function collection(): Collection
    {
        $rows = collect($this->scoring['scored_rows'] ?? []);
        $weights = $this->scoring['weights'] ?? [];

        $titleRow = collect([array_fill(0, 11, '')]);

        $metaRow = collect([[
            'Formula Version',
            (string) ($this->scoring['formula_version'] ?? 'v2_tasks'),
            'Task Weight',
            (string) ($weights['tasks'] ?? 0),
            'Punctuality Weight',
            (string) ($weights['punctuality'] ?? 0),
            'Work Hours Weight',
            (string) ($weights['work_hours'] ?? 0),
            'Vote Weight',
            (string) ($weights['vote'] ?? 0),
            '',
        ]]);

        $headerRow = collect([[
            '#',
            'Employee Name',
            'AC-No',
            'Final Score',
            'TaskScore',
            'VoteScore',
            'WorkHoursScore',
            'PunctualityScore',
            'Assigned Tasks',
            'Evaluated Tasks',
            'Generated At',
        ]]);

        $dataRows = $rows->values()->map(function (array $row, int $index) {
            $employee = $row['employee'];
            $breakdown = $row['breakdown'] ?? [];
            $raw = $breakdown['raw_inputs'] ?? [];

            return [
                $index + 1,
                (string) ($employee->name ?? ''),
                (string) ($employee->ac_no ?? ''),
                (float) ($row['final_score'] ?? 0),
                (float) ($breakdown['task_score'] ?? 0),
                (float) ($breakdown['vote_score'] ?? 0),
                (float) ($breakdown['work_hours_score'] ?? 0),
                (float) ($breakdown['punctuality_score'] ?? 0),
                (int) ($raw['assigned_tasks_count'] ?? 0),
                (int) ($raw['evaluated_tasks_count'] ?? 0),
                now()->format('Y-m-d H:i'),
            ];
        });

        return $titleRow->concat($metaRow)->concat($headerRow)->concat($dataRows);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 24,
            'C' => 14,
            'D' => 14,
            'E' => 13,
            'F' => 13,
            'G' => 15,
            'H' => 16,
            'I' => 14,
            'J' => 14,
            'K' => 20,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $monthLabel = \Carbon\Carbon::create($this->year, $this->month, 1)->locale('ar')->isoFormat('MMMM YYYY');
        $lastRow = collect($this->scoring['scored_rows'] ?? [])->count() + 3;

        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', "Employee Of Month Ranking - {$monthLabel}");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF31719D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->getStyle('A2:K2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF1E293B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A3:K3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF1E293B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2E8F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF94A3B8']],
            ],
        ]);

        if ($lastRow > 3) {
            $sheet->getStyle("A4:K{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $sheet->getStyle("B4:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("D4:D{$lastRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FF0F766E']],
            ]);
            $sheet->getStyle("E4:E{$lastRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FF047857']],
            ]);

            foreach (range(4, $lastRow) as $row) {
                if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:K{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFF8FAFC');
                }
            }
        }

        return [];
    }
}
