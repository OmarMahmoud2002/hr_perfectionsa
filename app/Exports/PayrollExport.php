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
     *
     * الأعمدة (17 عمود A→Q):
     *   A=#  B=الموظف  C=رقم الكارت  D=أيام العمل  E=أيام الحضور  F=أيام الغياب
     *   G=التأخير(س)  H=OT(س)  I=الفرق(س)  J=المرتب الأساسي
     *   K=خصم التأخير  L=خصم الغياب  M=مكافأة OT  N=بونص حضور
     *   O=بونص إضافي  P=خصم إضافي  Q=صافي المرتب
     */
    public function collection(): Collection
    {
        // صف 1: placeholder للعنوان (سيُستبدل في styles)
        $titleRow = collect([array_fill(0, 17, '')]);

        // صف 2: عناوين الأعمدة
        $headerRow = collect([[
            '#', 'الموظف', 'رقم الكارت', 'أيام العمل', 'أيام الحضور', 'أيام الغياب',
            'التأخير (س)', 'OT (س)', 'الفرق (س)', 'المرتب الأساسي',
            'خصم التأخير', 'خصم الغياب', 'مكافأة OT', 'بونص حضور',
            'بونص إضافي', 'خصم إضافي', 'صافي المرتب',
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
                round($report->total_late_minutes / 60, 2),
                round($report->total_overtime_minutes / 60, 2),
                round(($report->total_overtime_minutes - $report->total_late_minutes) / 60, 2),
                $report->basic_salary,
                $report->late_deduction,
                $report->absent_deduction,
                $report->overtime_bonus,
                $report->attendance_bonus,
                $report->extra_bonus,
                $report->extra_deduction,
                $report->net_salary_final,
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
            'N' => 14,
            'O' => 14,
            'P' => 14,
            'Q' => 16,
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
        $sheet->mergeCells('A1:Q1');
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
        $sheet->getStyle('A2:Q2')->applyFromArray([
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
            $sheet->getStyle("A3:Q{$lastRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']],
                ],
            ]);

            // عمود اسم الموظف يسار
            $sheet->getStyle("B3:B{$lastRow}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // عمود صافي المرتب (Q) — تلوين
            $sheet->getStyle("Q3:Q{$lastRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FF317C77']],
            ]);

            // عمود بونص الحضور (N) — تلوين ذهبي
            $sheet->getStyle("N3:N{$lastRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFD97706']],
            ]);

            // عمود بونص إضافي (O) — تلوين أخضر
            $sheet->getStyle("O3:O{$lastRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FF10B981']],
            ]);

            // عمود خصم إضافي (P) — تلوين أحمر
            $sheet->getStyle("P3:P{$lastRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFDC2626']],
            ]);

            // عمود الفرق (I) — تلوين حسب القيمة (أخضر موجب، أحمر سالب)
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
                    $sheet->getStyle("A{$row}:Q{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFF8FAFC');
                }
            }
        }

        // ==================
        // صف الإجمالي (بعد آخر صف بيانات)
        // ==================
        $totalRow = $lastRow + 1;
        $sheet->getStyle("A{$totalRow}:Q{$totalRow}")->applyFromArray([
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
        $sheet->setCellValue("N{$totalRow}", $this->reports->sum('attendance_bonus'));
        $sheet->setCellValue("O{$totalRow}", $this->reports->sum('extra_bonus'));
        $sheet->setCellValue("P{$totalRow}", $this->reports->sum('extra_deduction'));
        $sheet->setCellValue("Q{$totalRow}", $this->reports->sum('net_salary_final'));

        // RTL للشيت كاملاً
        $sheet->setRightToLeft(true);

        return [];
    }
}
