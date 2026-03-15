<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AttendanceEmployeeExport implements FromCollection, WithStyles, WithTitle, WithColumnWidths
{
    public function __construct(
        private readonly Employee   $employee,
        private readonly Collection $dailyBreakdown,
        private readonly array      $stats,
        private readonly int        $month,
        private readonly int        $year,
    ) {}

    public function title(): string
    {
        return "حضور_{$this->month}_{$this->year}";
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, // التاريخ
            'B' => 12, // اليوم
            'C' => 12, // الحضور
            'D' => 12, // الانصراف
            'E' => 14, // التأخير
            'F' => 14, // Overtime
            'G' => 16, // الحالة
        ];
    }

    public function collection(): Collection
    {
        $monthLabel = \Carbon\Carbon::create($this->year, $this->month, 1)
            ->locale('ar')->isoFormat('MMMM YYYY');

        // صف العنوان
        $titleRow = collect([array_fill(0, 7, '')]);

        // صف الترويسة
        $headerRow = collect([[
            'التاريخ', 'اليوم', 'الحضور', 'الانصراف', 'التأخير', 'Overtime', 'الحالة',
        ]]);

        // صفوف البيانات
        $dataRows = $this->dailyBreakdown->map(function ($day) {
            $record = $day['record'];

            $statusMap = [
                'present'        => 'حاضر',
                'late'           => 'متأخر',
                'absent'         => 'غائب',
                'public_holiday' => 'إجازة رسمية',
                'friday'         => 'جمعة',
                'weekly_leave'   => 'إجازة أسبوعية',
            ];

            $lateText = '';
            if ($record && $record->late_minutes > 0) {
                $h = floor($record->late_minutes / 60);
                $m = $record->late_minutes % 60;
                $lateText = ($h > 0 ? "{$h}س " : '') . "{$m}د";
            }

            $otText = '';
            if ($record && $record->overtime_minutes > 0) {
                $h = floor($record->overtime_minutes / 60);
                $m = $record->overtime_minutes % 60;
                $otText = ($h > 0 ? "{$h}س " : '') . "{$m}د";
            }

            return [
                $day['date']->format('Y-m-d'),
                $day['day_name'],
                ($record && $record->clock_in)  ? substr($record->clock_in, 0, 5)  : '—',
                ($record && $record->clock_out) ? substr($record->clock_out, 0, 5) : '—',
                $lateText ?: '—',
                $otText   ?: '—',
                $statusMap[$day['status']] ?? $day['status'],
            ];
        });

        // صف الملخص
        $lateTotalMin = (int) $this->stats['total_late_minutes'];
        $otTotalMin   = (int) $this->stats['total_overtime_minutes'];
        $lateH = floor($lateTotalMin / 60); $lateM = $lateTotalMin % 60;
        $otH   = floor($otTotalMin   / 60); $otM   = $otTotalMin   % 60;

        $summaryRow = collect([[
            'الإجمالي',
            '',
            '',
            '',
            ($lateH > 0 ? "{$lateH}س " : '') . "{$lateM}د",
            ($otH   > 0 ? "{$otH}س "   : '') . "{$otM}د",
            'حضور: ' . $this->stats['total_present_days'] . ' | غياب: ' . $this->stats['total_absent_days'],
        ]]);

        return $titleRow->concat($headerRow)->concat($dataRows)->concat($summaryRow);
    }

    public function styles(Worksheet $sheet): array
    {
        $monthLabel   = \Carbon\Carbon::create($this->year, $this->month, 1)
            ->locale('ar')->isoFormat('MMMM YYYY');
        $totalDataRows = $this->dailyBreakdown->count();
        $lastDataRow   = $totalDataRows + 2; // +2 (عنوان + ترويسة)
        $summaryRow    = $lastDataRow + 1;

        // ==================
        // صف العنوان
        // ==================
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', "تقرير حضور — {$this->employee->name} — {$monthLabel}");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF31719D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // ==================
        // صف الترويسة
        // ==================
        $sheet->getStyle('A2:G2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF1E293B']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2E8F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN,   'color' => ['argb' => 'FFCBD5E1']],
                'bottom'     => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF94A3B8']],
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // ==================
        // صفوف البيانات
        // ==================
        if ($totalDataRows > 0) {
            $sheet->getStyle("A3:G{$lastDataRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders'   => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']],
                ],
            ]);

            // عمود اليوم والحالة يسار
            $sheet->getStyle("B3:B{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("G3:G{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // تلوين حسب الحالة (أعمدة G)
            foreach ($this->dailyBreakdown as $i => $day) {
                $row = $i + 3;
                $fillArgb = match($day['status']) {
                    'present'        => 'FFD1FAE5', // emerald-100
                    'late'           => 'FFFEF3C7', // amber-100
                    'absent'         => 'FFFEE2E2', // red-100
                    'public_holiday' => 'FFE0F2FE', // sky-100
                    'weekly_leave'   => 'FFEDE9FE', // purple-100
                    default          => 'FFF8FAFC', // slate-50
                };
                $sheet->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($fillArgb);
            }
        }

        // ==================
        // صف الإجمالي
        // ==================
        $sheet->getStyle("A{$summaryRow}:G{$summaryRow}")->applyFromArray([
            'font'  => ['bold' => true, 'color' => ['argb' => 'FF1E293B']],
            'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FDF4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF10B981']],
            ],
        ]);
        $sheet->getStyle("A{$summaryRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G{$summaryRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // RTL
        $sheet->setRightToLeft(true);

        return [];
    }
}
