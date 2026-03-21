<?php

namespace App\Services\Import;

use App\DTOs\AttendanceRowDTO;
use App\Enums\ImportStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\Setting;
use App\Services\Attendance\AttendanceCalculationService;
use App\Services\Attendance\PublicHolidayService;
use App\Services\Dashboard\DashboardStatisticsService;
use App\Services\Employee\EmployeeService;
use App\Services\Excel\ExcelTimeHelper;
use App\Services\Excel\ExcelParserService;
use App\Services\Excel\ExcelReaderService;
use App\Services\Payroll\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportService
{
    public function __construct(
        private readonly ExcelReaderService           $reader,
        private readonly ExcelParserService           $parser,
        private readonly EmployeeService              $employeeService,
        private readonly AttendanceCalculationService $calculator,
        private readonly PublicHolidayService         $holidayService,
        private readonly DashboardStatisticsService   $dashboardStats,
    ) {}

    // ===================================================
    // المرحلة 1: قراءة وتحليل الملف فقط (بدون حفظ)
    // ===================================================

    /**
     * قراءة وتحليل ملف Excel وإنشاء ImportBatch بحالة pending
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  int  $userId
     * @return array{batch: ImportBatch, preview: array, errors: array}
     * @throws \Exception
     */
    public function uploadAndPreview($file, int $userId): array
    {
        // تنظيف اسم الملف من أي محتوى خطير (XSS Prevention)
        // إزالة أي أحرف خاصة وعلامات HTML المحتملة
        $originalName = $file->getClientOriginalName();
        $fileName = $this->sanitizeFileName($originalName);
        $filePath = $file->store('imports', 'local');

        try {
            [$month, $year] = $this->detectDominantPayrollPeriodFromFile($filePath);

            if ($month === null || $year === null) {
                throw new \Exception('لم يتم العثور على بيانات صالحة في الملف.');
            }

            [$recordsCount, $employeesCount, $employeeNames, $parseErrors] = $this->collectPreviewStatsFromFile(
                $filePath,
                $month,
                $year
            );

            if ($employeesCount === 0) {
                throw new \Exception('لم يتم العثور على أي موظفين في الملف.');
            }
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($filePath);
            throw $e;
        }

        // حذف أي دفعات قديمة في حالة الانتظار أو المعالجة لنفس الشهر
        // (تنظيف الرفعات الناقصة السابقة قبل إنشاء الجديدة)
        ImportBatch::where('month', $month)
            ->where('year', $year)
            ->whereIn('status', [ImportStatus::Pending, ImportStatus::Processing])
            ->each(function ($oldBatch) {
                Storage::disk('local')->delete($oldBatch->file_path);
                $oldBatch->publicHolidays()->delete();
                $oldBatch->delete();
            });

        // إنشاء ImportBatch بحالة pending
        $batch = ImportBatch::create([
            'file_name'       => $fileName,
            'file_path'       => $filePath,
            'month'           => $month,
            'year'            => $year,
            'status'          => ImportStatus::Pending,
            'records_count'   => $recordsCount,
            'employees_count' => $employeesCount,
            'uploaded_by'     => $userId,
        ]);

        // معاينة مختصرة
        $preview = [
            'month'            => $month,
            'year'             => $year,
            'employees_count'  => $employeesCount,
            'records_count'    => $batch->records_count,
            'employee_names'   => $employeeNames,
            'parse_errors'     => $parseErrors,
        ];

        // هل يوجد بيانات مكررة لنفس الشهر؟
        $existingBatch = ImportBatch::where('month', $month)
            ->where('year', $year)
            ->where('status', ImportStatus::Completed)
            ->where('id', '!=', $batch->id)
            ->first();

        $preview['has_duplicate'] = $existingBatch !== null;
        $preview['existing_batch'] = $existingBatch;

        return [
            'batch'   => $batch,
            'preview' => $preview,
            'errors'  => $parseErrors,
        ];
    }

    // ===================================================
    // المرحلة 2: تنفيذ الاستيراد الفعلي بعد التأكيد
    // ===================================================

    /**
     * تنفيذ الاستيراد الكامل بعد تأكيد المستخدم.
     *
     * السلوك الحالي: overwrite-only لنفس فترة الرواتب.
     *
     * @param  ImportBatch  $batch
     * @param  array        $importSettings   إعدادات مخصصة لهذا الشهر
     * @param  bool         $replaceExisting  متروك للتوافق الخلفي (لا يؤثر)
     * @return ImportBatch
     * @throws \Exception
     */
    public function processImport(ImportBatch $batch, array $importSettings = [], bool $replaceExisting = false): ImportBatch
    {
        // حفظ الإعدادات المخصصة
        if (!empty($importSettings)) {
            $batch->update(['import_settings' => $importSettings]);
        }

        // الإعدادات النهائية للحساب
        $settings = $this->resolveSettings($importSettings);

        // بدء المعالجة
        $batch->update(['status' => ImportStatus::Processing]);

        try {
            DB::transaction(function () use ($batch, $settings): void {
                // overwrite-only: احذف كل سجلات الفترة أولاً.
                $this->deleteAttendanceRecordsForPayrollPeriod($batch->month, $batch->year);
                // تنظيف دفعات الشهر السابقة حتى تبقى دفعة مكتملة واحدة للشهر.
                $this->cleanupPreviousBatchesForMonth($batch->month, $batch->year, $batch->id);

                // جلب الإجازات الرسمية
                $publicHolidays = $this->holidayService->getHolidayDates($batch);

                $totalRecords   = 0;
                $totalEmployees = 0;

                $columns = null;
                $seenEmployeeIds = [];
                $employeeCache = [];
                $employeeSettingsCache = [];
                $recordsBuffer = [];

                $this->reader->processRowsFromPath($batch->file_path, function (array $rows) use (
                    &$columns,
                    &$seenEmployeeIds,
                    &$employeeCache,
                    &$employeeSettingsCache,
                    &$recordsBuffer,
                    &$totalRecords,
                    &$totalEmployees,
                    $batch,
                    $settings,
                    $publicHolidays
                ) {
                    foreach ($rows as $row) {
                        if ($this->isEmptyExcelRow($row)) {
                            continue;
                        }

                        if ($columns === null) {
                            $columns = $this->parser->detectColumns($row);

                            // إذا الصف الأول Header نتخطاه، وإن كان Data نكمل مع نفس الصف.
                            if (!$this->isDataRow($row, $columns)) {
                                continue;
                            }
                        }

                        $rowDTO = $this->buildAttendanceRowDTO($row, $columns, $batch->month, $batch->year);
                        if ($rowDTO === null) {
                            continue;
                        }

                        // تجاهل أيام الجمعة
                        if ($rowDTO->date->dayOfWeek === 5) {
                            continue;
                        }

                        $employee = $employeeCache[$rowDTO->acNo] ??= $this->employeeService->findOrCreateFromExcel(
                            $rowDTO->acNo,
                            $rowDTO->name
                        );

                        if (!isset($seenEmployeeIds[$employee->id])) {
                            $seenEmployeeIds[$employee->id] = true;
                            $totalEmployees++;
                        }

                        $employeeSettings = $employeeSettingsCache[$employee->id]
                            ??= $this->resolveEmployeeSettings($employee, $settings);

                        $calc = $this->calculator->calculateDay($rowDTO, $employeeSettings, $publicHolidays);

                        $recordsBuffer[] = [
                            'employee_id'      => $employee->id,
                            'date'             => $rowDTO->date->toDateString(),
                            'clock_in'         => $rowDTO->clockIn?->format('H:i:s'),
                            'clock_out'        => $rowDTO->clockOut?->format('H:i:s'),
                            'is_absent'        => $calc['is_absent'],
                            'late_minutes'     => $calc['late_minutes'],
                            'overtime_minutes' => $calc['overtime_minutes'],
                            'work_minutes'     => $calc['work_minutes'],
                            'notes'            => $calc['notes'],
                            'import_batch_id'  => $batch->id,
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ];

                        $totalRecords++;

                        if (count($recordsBuffer) >= 1000) {
                            $this->flushAttendanceBuffer($recordsBuffer);
                        }
                    }
                }, 1000);

                $this->flushAttendanceBuffer($recordsBuffer);

                // تحديث الدفعة
                $batch->update([
                    'status'          => ImportStatus::Completed,
                    'records_count'   => $totalRecords,
                    'employees_count' => $totalEmployees,
                ]);
            });

            $this->dashboardStats->clearCache();

            return $batch->fresh();

        } catch (\Exception $e) {
            $batch->update([
                'status'    => ImportStatus::Failed,
                'error_log' => $e->getMessage(),
            ]);

            Log::error('ImportService: فشل في الاستيراد', [
                'batch_id' => $batch->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    // ===================================================
    // Hard Delete: حذف بيانات شهر كامل
    // ===================================================

    /**
     * حذف دفعة استيراد وجميع بياناتها المرتبطة
     */
    public function deleteBatch(ImportBatch $batch): void
    {
        DB::beginTransaction();
        try {
            // حذف سجلات الحضور (Hard Delete)
            AttendanceRecord::where('import_batch_id', $batch->id)->delete();

            // حذف الإجازات الرسمية
            $batch->publicHolidays()->delete();

            // حذف الملف من Storage
            if ($batch->file_path && Storage::disk('local')->exists($batch->file_path)) {
                Storage::disk('local')->delete($batch->file_path);
            }

            // حذف الدفعة نفسها
            $batch->delete();

            DB::commit();

            $this->dashboardStats->clearCache();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ===================================================
    // مساعدات خاصة
    // ===================================================

    /**
     * overwrite-only: حذف كل سجلات الحضور لفترة الرواتب المطلوبة.
     */
    private function deleteAttendanceRecordsForPayrollPeriod(int $month, int $year): void
    {
        [$periodStart, $periodEnd] = PayrollPeriod::resolve($month, $year);

        AttendanceRecord::query()
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->delete();
    }

    /**
     * تنظيف دفعات الشهر السابقة بعد الاستبدال الكامل.
     */
    private function cleanupPreviousBatchesForMonth(int $month, int $year, int $excludeBatchId): void
    {
        $existingBatches = ImportBatch::where('month', $month)
            ->where('year', $year)
            ->where('id', '!=', $excludeBatchId)
            ->get();

        foreach ($existingBatches as $oldBatch) {
            $oldBatch->publicHolidays()->delete();

            if ($oldBatch->file_path && Storage::disk('local')->exists($oldBatch->file_path)) {
                Storage::disk('local')->delete($oldBatch->file_path);
            }

            $oldBatch->delete();
        }
    }

    /**
     * دمج الإعدادات المخصصة مع الإعدادات الافتراضية
     */
    private function resolveSettings(array $importSettings): array
    {
        $allSettings = Setting::getAllAsArray();

        $defaults = [
            'work_start_time'     => $allSettings['work_start_time'] ?? '09:00',
            'work_end_time'       => $allSettings['work_end_time'] ?? '17:00',
            'overtime_start_time' => $allSettings['overtime_start_time'] ?? '17:30',
            'late_grace_minutes'  => (int) ($allSettings['late_grace_minutes'] ?? 30),
        ];

        return array_merge($defaults, array_filter($importSettings, fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * دمج إعدادات الشيفت الخاصة بالموظف فوق إعدادات الدفعة
     * الأولوية: إعدادات الموظف > إعدادات الدفعة > الإعدادات الافتراضية
     */
    private function resolveEmployeeSettings(Employee $employee, array $batchSettings): array
    {
        return array_merge($batchSettings, $employee->getShiftOverrides());
    }

    /**
     * تجهيز صف Excel إلى DTO بعد التحقق من الشهر/السنة المطلوبة.
     */
    private function buildAttendanceRowDTO(array $row, array $columns, int $batchMonth, int $batchYear): ?AttendanceRowDTO
    {
        $acNo    = trim((string) ($row[$columns['ac_no']] ?? ''));
        $name    = trim((string) ($row[$columns['name']] ?? ''));
        $dateRaw = $row[$columns['date']] ?? null;

        if ($acNo === '' || $dateRaw === null || $dateRaw === '') {
            return null;
        }

        $date = ExcelTimeHelper::parseDate($dateRaw);
        if (!$date) {
            return null;
        }

        $payrollMonth = PayrollPeriod::monthForDate($date);
        if ($payrollMonth['month'] !== $batchMonth || $payrollMonth['year'] !== $batchYear) {
            return null;
        }

        $clockInRaw  = $row[$columns['clock_in']] ?? null;
        $clockOutRaw = $row[$columns['clock_out']] ?? null;

        $clockIn  = ExcelTimeHelper::parseTime($clockInRaw, $date);
        $clockOut = ExcelTimeHelper::parseTime($clockOutRaw, $date);

        $notes = null;
        if ($clockIn && $clockOut && $clockOut->lt($clockIn)) {
            $clockOut = null;
            $notes = 'وقت انصراف غير صالح - تم تجاهله';
        }

        $isAbsent = ($clockIn === null && $clockOut === null);

        if (!$isAbsent) {
            if ($clockIn === null) {
                $notes = ($notes ? $notes . '، ' : '') . 'حضور بدون بصمة (افتراضي 09:00)';
            }
            if ($clockOut === null) {
                $notes = ($notes ? $notes . '، ' : '') . 'انصراف بدون بصمة (افتراضي 17:00)';
            }
        }

        return new AttendanceRowDTO(
            acNo: $acNo,
            name: $name ?: $acNo,
            date: $date,
            clockIn: $clockIn,
            clockOut: $clockOut,
            isAbsent: $isAbsent,
            notes: $notes,
        );
    }

    /**
     * تنفيذ upsert على دفعة سجلات الحضور ثم تفريغ الذاكرة.
     */
    private function flushAttendanceBuffer(array &$recordsBuffer): void
    {
        if (empty($recordsBuffer)) {
            return;
        }

        AttendanceRecord::upsert(
            $recordsBuffer,
            ['employee_id', 'date'],
            ['clock_in', 'clock_out', 'is_absent', 'late_minutes', 'overtime_minutes', 'work_minutes', 'notes', 'import_batch_id', 'updated_at']
        );

        $recordsBuffer = [];
    }

    private function isDataRow(array $row, array $columns): bool
    {
        $acNoValue = $row[$columns['ac_no']] ?? null;

        return is_numeric($acNoValue) && $acNoValue > 0;
    }

    private function isEmptyExcelRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && $cell !== '' && $cell !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * المرور الأول: تحديد شهر الراتب السائد بدون تحميل الملف بالكامل.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function detectDominantPayrollPeriodFromFile(string $filePath): array
    {
        $columns = null;
        $monthCounts = [];

        $this->reader->processRowsFromPath($filePath, function (array $rows) use (&$columns, &$monthCounts) {
            foreach ($rows as $row) {
                if ($this->isEmptyExcelRow($row)) {
                    continue;
                }

                if ($columns === null) {
                    $columns = $this->parser->detectColumns($row);

                    if (!$this->isDataRow($row, $columns)) {
                        continue;
                    }
                }

                $dateRaw = $row[$columns['date']] ?? null;
                $acNo    = trim((string) ($row[$columns['ac_no']] ?? ''));

                if ($acNo === '' || $dateRaw === null || $dateRaw === '') {
                    continue;
                }

                $date = ExcelTimeHelper::parseDate($dateRaw);
                if (!$date) {
                    continue;
                }

                $payrollMonth = PayrollPeriod::monthForDate($date);
                $key = $payrollMonth['year'] . '_' . str_pad((string) $payrollMonth['month'], 2, '0', STR_PAD_LEFT);
                $monthCounts[$key] = ($monthCounts[$key] ?? 0) + 1;
            }
        }, 1000);

        if (empty($monthCounts)) {
            return [null, null];
        }

        arsort($monthCounts);
        [$year, $month] = explode('_', array_key_first($monthCounts));

        return [(int) $month, (int) $year];
    }

    /**
     * المرور الثاني: إحصائيات المعاينة للشهر السائد فقط.
     *
     * @return array{0: int, 1: int, 2: array<int, string>, 3: array<int, string>}
     */
    private function collectPreviewStatsFromFile(string $filePath, int $targetMonth, int $targetYear): array
    {
        $columns = null;
        $recordsCount = 0;
        $employeeNamesByAcNo = [];
        $errors = [];

        $this->reader->processRowsFromPath($filePath, function (array $rows) use (
            &$columns,
            &$recordsCount,
            &$employeeNamesByAcNo,
            &$errors,
            $targetMonth,
            $targetYear
        ) {
            foreach ($rows as $row) {
                if ($this->isEmptyExcelRow($row)) {
                    continue;
                }

                if ($columns === null) {
                    $columns = $this->parser->detectColumns($row);

                    if (!$this->isDataRow($row, $columns)) {
                        continue;
                    }
                }

                $acNo    = trim((string) ($row[$columns['ac_no']] ?? ''));
                $name    = trim((string) ($row[$columns['name']] ?? ''));
                $dateRaw = $row[$columns['date']] ?? null;

                if ($acNo === '' || $dateRaw === null || $dateRaw === '') {
                    continue;
                }

                $date = ExcelTimeHelper::parseDate($dateRaw);
                if (!$date) {
                    if (count($errors) < 20) {
                        $errors[] = 'تاريخ غير صالح للموظف: ' . ($name ?: $acNo);
                    }
                    continue;
                }

                $payrollMonth = PayrollPeriod::monthForDate($date);
                if ($payrollMonth['month'] !== $targetMonth || $payrollMonth['year'] !== $targetYear) {
                    continue;
                }

                $recordsCount++;
                if (!isset($employeeNamesByAcNo[$acNo])) {
                    $employeeNamesByAcNo[$acNo] = $name ?: $acNo;
                }
            }
        }, 1000);

        return [
            $recordsCount,
            count($employeeNamesByAcNo),
            array_slice(array_values($employeeNamesByAcNo), 0, 5),
            $errors,
        ];
    }

    /**
     * تنظيف اسم الملف من أي محتوى خطير لمنع XSS
     * يزيل العلامات الخطيرة ويحافظ على الأحرف الآمنة فقط
     */
    private function sanitizeFileName(string $fileName): string
    {
        // إزالة أي HTML/JavaScript tags
        $fileName = strip_tags($fileName);

        // إزالة الأحرف الخاصة الخطيرة (< > " ' / \ & ; | $ ` )
        $fileName = preg_replace('/[<>"\'\/\\\&;|$`]/', '', $fileName);

        // إزالة محاولات null bytes
        $fileName = str_replace("\0", '', $fileName);

        // الحد من الطول (أقصى 255 حرف)
        if (mb_strlen($fileName) > 255) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $baseName = mb_substr(pathinfo($fileName, PATHINFO_FILENAME), 0, 250);
            $fileName = $baseName . '.' . $extension;
        }

        // إذا أصبح الاسم فارغاً بعد التنظيف، استخدم اسم افتراضي
        if (empty(trim($fileName))) {
            $fileName = 'upload_' . time() . '.xlsx';
        }

        return $fileName;
    }
}
