<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PayrollExport implements FromCollection, WithStyles, WithTitle, WithColumnWidths
{
    public function __construct(
        private readonly Collection $reports,
        private readonly int        $month,
        private readonly int        $year,
    ) {}

    /**
     * عنوان الشيت
     */
    public function title(): string
    {
        return "مرتبات_{$this->month}_{$this->year}";
    }

    /**
     * بيانات الصفوف:
     * الصف 1: بيانات فارغة — سيُكتب فوقها عنوان الكشف في styles()
     * الصف 2: عناوين الأعمدة
     * الصف 3+: بيانات الموظفين
     */
    public function collection(): Collection
    {
        // صف 1: placeholder للعنوان (سيُستبدل في styles)
        $titleRow = collect([array_fill(0, 15, '')]);

        // صف 2: عناوين الأعمدة
        $headerRow = collect([[
            '#', 'الموظف', 'رقم الكارت', 'أيام العمل', 'أيام الحضور', 'أيام الغياب',
            'التأخير (د)', 'OT (د)', 'الفرق (د)', 'المرتب الأساسي',
            'خصم التأخير', 'خصم الغياب', 'مكافأة OT', 'صافي المرتب', 'الحالة',
        ]]);

        // صف 3+: بيانات الموظفين
        $dataRows = $this->reports->map(function ($report, $index) {
            return [
                $index + 1,
                $report->employee->name,
                $report->employee->ac_no,
                $report->total_working_days,
                $report->total_present_days,
                $report->total_absent_days,
                $report->total_late_minutes,
                $report->total_overtime_minutes,
                $report->total_overtime_minutes - $report->total_late_minutes,
                $report->basic_salary,
                $report->late_deduction,
                $report->absent_deduction,
                $report->overtime_bonus,
                $report->net_salary,
                $report->is_locked ? 'مؤمّن' : 'مفتوح',
            ];
        });

        return $titleRow->concat($headerRow)->concat($dataRows);
    }

    /**
     * عرض الأعمدة
     */
    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 25,
            'C' => 14,
            'D' => 12,
            'E' => 12,
            'F' => 12,
            'G' => 14,
            'H' => 12,
            'I' => 12,
            'J' => 16,
            'K' => 14,
            'L' => 14,
            'M' => 14,
            'N' => 16,
            'O' => 10,
        ];
    }

    /**
     * تنسيق الشيت
     */
    public function styles(Worksheet $sheet): array
    {
        // الصف 1 = عنوان، الصف 2 = ترويسة الأعمدة، الصف 3+ = البيانات
        $lastRow = $this->reports->count() + 2; // +2 (عنوان + ترويسة)

        // ==================
        // عنوان الكشف (الصف 1)
        // ==================
        $monthLabel = \Carbon\Carbon::create($this->year, $this->month, 1)->locale('ar')->isoFormat('MMMM YYYY');
        $sheet->mergeCells('A1:O1');
        $sheet->setCellValue('A1', "كشف مرتبات — {$monthLabel}");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF31719D'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // ==================
        // ترويسة الأعمدة (الصف 2)
        // ==================
        $sheet->getStyle('A2:O2')->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 10,
                'color' => ['argb' => 'FF1E293B'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE2E8F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']],
                'bottom'     => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF94A3B8']],
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ==================
        // بيانات الجدول (الصف 3 → $lastRow)
        // ==================
        if ($lastRow > 2) {
            $sheet->getStyle("A3:O{$lastRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']],
                ],
            ]);

            // عمود اسم الموظف يسار
            $sheet->getStyle("B3:B{$lastRow}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // عمود صافي المرتب — تلوين
            $sheet->getStyle("N3:N{$lastRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FF317C77']],
            ]);

            // عمود الفرق — تلوين حسب القيمة (أخضر موجب، أحمر سالب)
            foreach (range(3, $lastRow) as $row) {
                $diff = $sheet->getCell("I{$row}")->getValue();
                if (is_numeric($diff)) {
                    $color = $diff >= 0 ? 'FF059669' : 'FFDC2626';
                    $sheet->getStyle("I{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => $color]],
                    ]);
                }
            }

            // تلوين صفوف التبادل (تبدأ من صف 3)
            foreach (range(3, $lastRow) as $row) {
                if ($row % 2 === 1) {
                    $sheet->getStyle("A{$row}:O{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFF8FAFC');
                }
            }
        }

        // ==================
        // صف الإجمالي (بعد آخر صف بيانات)
        // ==================
        $totalRow = $lastRow + 1;
        $sheet->getStyle("A{$totalRow}:O{$totalRow}")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FF1E293B'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF0FDF4'],
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF10B981']],
            ],
        ]);

        $sheet->mergeCells("A{$totalRow}:H{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'الإجمالي');
        $sheet->setCellValue("J{$totalRow}", $this->reports->sum('basic_salary'));
        $sheet->setCellValue("K{$totalRow}", $this->reports->sum('late_deduction'));
        $sheet->setCellValue("L{$totalRow}", $this->reports->sum('absent_deduction'));
        $sheet->setCellValue("M{$totalRow}", $this->reports->sum('overtime_bonus'));
        $sheet->setCellValue("N{$totalRow}", $this->reports->sum('net_salary'));

        // RTL للشيت كاملاً
        $sheet->setRightToLeft(true);

        return [];
    }
}
