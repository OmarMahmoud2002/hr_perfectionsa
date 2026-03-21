<?php

namespace App\Services\Excel;

use App\DTOs\AttendanceRowDTO;
use App\DTOs\EmployeeAttendanceDTO;
use App\Services\Payroll\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * تحليل صفوف Excel وتحويلها إلى DTOs
 */
class ExcelParserService
{
    /**
     * فهرسة الأعمدة بناءً على الصف الأول (Header Row)
     *
     * @param  array  $headerRow
     * @return array{ac_no: int, name: int, date: int, clock_in: int, clock_out: int}
     */
    public function detectColumns(array $headerRow): array
    {
        // الترتيب الافتراضي: A=0, B=1, C=2, D=3, E=4
        $defaults = [
            'ac_no'     => 0,
            'name'      => 1,
            'date'      => 2,
            'clock_in'  => 3,
            'clock_out' => 4,
        ];

        if (empty($headerRow)) {
            return $defaults;
        }

        // محاولة اكتشاف تلقائي من أسماء الأعمدة
        $map = [];
        foreach ($headerRow as $index => $header) {
            $h = strtolower(trim((string) $header));

            if (in_array($h, ['ac no', 'ac-no', 'acno', 'employee no', 'emp no', 'id', 'رقم', 'رقم الموظف'])) {
                $map['ac_no'] = $index;
            } elseif (in_array($h, ['name', 'employee name', 'اسم', 'الاسم'])) {
                $map['name'] = $index;
            } elseif (in_array($h, ['date', 'تاريخ', 'التاريخ'])) {
                $map['date'] = $index;
            } elseif (in_array($h, ['clock in', 'clockin', 'check in', 'in time', 'حضور', 'وقت الحضور', 'time in'])) {
                $map['clock_in'] = $index;
            } elseif (in_array($h, ['clock out', 'clockout', 'check out', 'out time', 'انصراف', 'وقت الانصراف', 'time out'])) {
                $map['clock_out'] = $index;
            }
        }

        return array_merge($defaults, $map);
    }

    /**
     * تحليل جميع الصفوف وتحويلها إلى Collection<EmployeeAttendanceDTO>
     *
     * @param  array  $rows     جميع صفوف Excel (بما فيها الـ Header)
     * @param  array  $columns  خريطة الأعمدة
     * @return array{employees: Collection, month: int, year: int, errors: array}
     */
    public function parse(array $rows, array $columns): array
    {
        $errors    = [];
        $rowGroups = []; // مجمعة حسب ac_no

        // تخطي الصف الأول (Header) إن لم يكن يحتوي على بيانات رقمية
        $startIndex = 0;
        if (!empty($rows) && !$this->isDataRow($rows[0], $columns)) {
            $startIndex = 1;
        }

        // === المرور الأول: تحديد شهر الراتب السائد ===
        // الملف قد يحتوي على تواريخ من الشهر الميلادي كاملاً (مثلاً 1 مارس – 31 مارس)
        // لكن شهر أبريل الراتبي هو 22 مارس – 21 أبريل.
        // نحسب عدد الصفوف لكل شهر راتب ونختار الأكثر.
        $monthCounts = [];
        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            if ($this->isEmptyRow($row)) continue;
            $acNo    = trim((string) ($row[$columns['ac_no']] ?? ''));
            $dateRaw = $row[$columns['date']] ?? null;
            if (empty($acNo) || empty($dateRaw)) continue;
            $date = ExcelTimeHelper::parseDate($dateRaw);
            if (!$date) continue;
            $payroll = $this->getPayrollMonth($date);
            $key = $payroll['year'] . '_' . str_pad($payroll['month'], 2, '0', STR_PAD_LEFT);
            $monthCounts[$key] = ($monthCounts[$key] ?? 0) + 1;
        }

        if (empty($monthCounts)) {
            return ['employees' => collect(), 'month' => null, 'year' => null, 'errors' => $errors];
        }

        // الشهر السائد (الأكثر صفوفاً)
        arsort($monthCounts);
        $dominantKey = array_key_first($monthCounts);
        [$dominantYear, $dominantMonth] = explode('_', $dominantKey);
        $month = (int) $dominantMonth;
        $year  = (int) $dominantYear;

        // === المرور الثاني: معالجة صفوف شهر الراتب السائد فقط ===
        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];

            // تجاهل الصفوف الفارغة
            if ($this->isEmptyRow($row)) {
                continue;
            }

            // استخراج القيم
            $acNo      = trim((string) ($row[$columns['ac_no']] ?? ''));
            $name      = trim((string) ($row[$columns['name']] ?? ''));
            $dateRaw   = $row[$columns['date']] ?? null;
            $clockInRaw  = $row[$columns['clock_in']] ?? null;
            $clockOutRaw = $row[$columns['clock_out']] ?? null;

            // التحقق من الأعمدة الأساسية
            if (empty($acNo) || empty($dateRaw)) {
                continue;
            }

            // تحليل التاريخ
            $date = ExcelTimeHelper::parseDate($dateRaw);
            if (!$date) {
                $errors[] = "الصف " . ($i + 1) . ": تنسيق التاريخ غير صالح ({$dateRaw})";
                continue;
            }

            // تحويل التاريخ إلى شهر الراتب
            // إذا كان اليوم >= 22 → ينتمي لشهر الراتب التالي
            // إذا كان اليوم <= 21 → ينتمي لشهر الراتب الحالي
            $payroll = $this->getPayrollMonth($date);

            // تجاهل الصفوف التي لا تنتمي لشهر الراتب السائد (بدون خطأ)
            if ($payroll['month'] !== $month || $payroll['year'] !== $year) {
                continue;
            }

            // تحليل الأوقات
            $clockIn  = ExcelTimeHelper::parseTime($clockInRaw, $date);
            $clockOut = ExcelTimeHelper::parseTime($clockOutRaw, $date);

            // تحقق: clock_out قبل clock_in
            $notes = null;
            if ($clockIn && $clockOut && $clockOut->lt($clockIn)) {
                $errors[] = "الصف " . ($i + 1) . " (الموظف: {$name}, التاريخ: {$date->toDateString()}): وقت الانصراف قبل وقت الحضور";
                $clockOut = null;
                $notes = 'وقت انصراف غير صالح - تم تجاهله';
            }

            // تحديد الغياب
            $isAbsent = ($clockIn === null && $clockOut === null);

            // القيم الافتراضية
            if (!$isAbsent) {
                if ($clockIn === null) {
                    $notes = ($notes ? $notes . '، ' : '') . 'حضور بدون بصمة (افتراضي 09:00)';
                }
                if ($clockOut === null) {
                    $notes = ($notes ? $notes . '، ' : '') . 'انصراف بدون بصمة (افتراضي 17:00)';
                }
            }

            $dto = new AttendanceRowDTO(
                acNo:     $acNo,
                name:     $name ?: $acNo,
                date:     $date,
                clockIn:  $clockIn,
                clockOut: $clockOut,
                isAbsent: $isAbsent,
                notes:    $notes,
            );

            $rowGroups[$acNo][] = $dto;
        }

        // تجميع البيانات حسب الموظف
        $employees = collect();
        foreach ($rowGroups as $acNo => $records) {
            $employeeName = $records[0]->name;
            $employees->push(new EmployeeAttendanceDTO(
                acNo:    $acNo,
                name:    $employeeName,
                records: collect($records),
            ));
        }

        return [
            'employees' => $employees,
            'month'     => $month,
            'year'      => $year,
            'errors'    => $errors,
        ];
    }

    /**
     * هل الصف يحتوي على بيانات (ليس Header)
     */
    private function isDataRow(array $row, array $columns): bool
    {
        $acNoValue = $row[$columns['ac_no']] ?? null;
        return is_numeric($acNoValue) && $acNoValue > 0;
    }

    /**
     * هل الصف فارغ تماماً
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && $cell !== '' && $cell !== false) {
                return false;
            }
        }
        return true;
    }

    /**
     * تحويل تاريخ من ملف Excel إلى شهر الراتب المقابل
     * - إذا كان اليوم >= 22 → ينتمي لشهر الراتب التالي (22 فبراير → شهر مارس)
     * - إذا كان اليوم <= 21 → ينتمي لشهر الراتب الحالي (21 مارس → شهر مارس)
     *
     * @return array{month: int, year: int}
     */
    private function getPayrollMonth(Carbon $date): array
    {
        return PayrollPeriod::monthForDate($date);
    }
}
