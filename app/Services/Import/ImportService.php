<?php

namespace App\Services\Import;

use App\DTOs\AttendanceRowDTO;
use App\DTOs\EmployeeAttendanceDTO;
use App\Enums\ImportStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\Setting;
use App\Services\Attendance\AttendanceCalculationService;
use App\Services\Attendance\PublicHolidayService;
use App\Services\Employee\EmployeeService;
use App\Services\Excel\ExcelParserService;
use App\Services\Excel\ExcelReaderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportService
{
    public function __construct(
        private readonly ExcelReaderService           $reader,
        private readonly ExcelParserService           $parser,
        private readonly EmployeeService              $employeeService,
        private readonly AttendanceCalculationService $calculator,
        private readonly PublicHolidayService         $holidayService,
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
        // قراءة الملف
        $rows = $this->reader->readUploadedFile($file);

        if (empty($rows)) {
            throw new \Exception('الملف فارغ أو لا يحتوي على بيانات.');
        }

        // اكتشاف الأعمدة
        $columns = $this->parser->detectColumns($rows[0] ?? []);

        // تحليل البيانات
        $result = $this->parser->parse($rows, $columns);

        if ($result['month'] === null) {
            throw new \Exception('لم يتم العثور على بيانات صالحة في الملف.');
        }

        /** @var \Illuminate\Support\Collection $employees */
        $employees = $result['employees'];

        if ($employees->isEmpty()) {
            throw new \Exception('لم يتم العثور على أي موظفين في الملف.');
        }

        // حفظ الملف في Storage
        $fileName  = $file->getClientOriginalName();
        $filePath  = $file->store('imports', 'local');

        // حذف أي دفعات قديمة في حالة الانتظار أو المعالجة لنفس الشهر
        // (تنظيف الرفعات الناقصة السابقة قبل إنشاء الجديدة)
        ImportBatch::where('month', $result['month'])
            ->where('year', $result['year'])
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
            'month'           => $result['month'],
            'year'            => $result['year'],
            'status'          => ImportStatus::Pending,
            'records_count'   => $employees->sum(fn ($e) => $e->records->count()),
            'employees_count' => $employees->count(),
            'uploaded_by'     => $userId,
        ]);

        // معاينة مختصرة
        $preview = [
            'month'            => $result['month'],
            'year'             => $result['year'],
            'employees_count'  => $employees->count(),
            'records_count'    => $batch->records_count,
            'employee_names'   => $employees->take(5)->pluck('name')->all(),
            'parse_errors'     => $result['errors'],
        ];

        // هل يوجد بيانات مكررة لنفس الشهر؟
        $existingBatch = ImportBatch::where('month', $result['month'])
            ->where('year', $result['year'])
            ->where('status', ImportStatus::Completed)
            ->where('id', '!=', $batch->id)
            ->first();

        $preview['has_duplicate'] = $existingBatch !== null;
        $preview['existing_batch'] = $existingBatch;

        return [
            'batch'   => $batch,
            'preview' => $preview,
            'errors'  => $result['errors'],
        ];
    }

    // ===================================================
    // المرحلة 2: تنفيذ الاستيراد الفعلي بعد التأكيد
    // ===================================================

    /**
     * تنفيذ الاستيراد الكامل بعد تأكيد المستخدم
     *
     * @param  ImportBatch  $batch
     * @param  array        $importSettings   إعدادات مخصصة لهذا الشهر
     * @param  bool         $replaceExisting  هل تستبدل البيانات القديمة
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

        DB::beginTransaction();
        try {
            // حذف البيانات القديمة إن طُلب ذلك
            if ($replaceExisting) {
                $this->deleteExistingDataForMonth($batch->month, $batch->year, $batch->id);
            }

            // إعادة قراءة الملف
            $rows    = $this->reader->readFromPath($batch->file_path);
            $columns = $this->parser->detectColumns($rows[0] ?? []);
            $result  = $this->parser->parse($rows, $columns);

            /** @var \Illuminate\Support\Collection $employees */
            $employees = $result['employees'];

            // جلب الإجازات الرسمية
            $publicHolidays = $this->holidayService->getHolidayDates($batch);

            $totalRecords  = 0;
            $totalEmployees = 0;

            foreach ($employees as $employeeDTO) {
                /** @var EmployeeAttendanceDTO $employeeDTO */

                // إنشاء/تحديث الموظف
                $employee = $this->employeeService->findOrCreateFromExcel(
                    $employeeDTO->acNo,
                    $employeeDTO->name
                );

                // إعدادات الشيفت الخاصة بهذا الموظف تتجاوز إعدادات الدفعة
                $employeeSettings = $this->resolveEmployeeSettings($employee, $settings);

                $totalEmployees++;
                $recordsToInsert = [];

                foreach ($employeeDTO->records as $rowDTO) {
                    /** @var AttendanceRowDTO $rowDTO */

                    // حساب التأخير والـ OT بإعدادات الموظف الخاص
                    $calc = $this->calculator->calculateDay(
                        $rowDTO,
                        $employeeSettings,
                        $publicHolidays
                    );

                    // تجاهل أيام الجمعة
                    if ($rowDTO->date->dayOfWeek === 5) {
                        continue;
                    }

                    $recordsToInsert[] = [
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
                }

                // إدراج بالجملة مع تجاهل المكرر (upsert على employee_id + date)
                if (!empty($recordsToInsert)) {
                    AttendanceRecord::upsert(
                        $recordsToInsert,
                        ['employee_id', 'date'],
                        ['clock_in', 'clock_out', 'is_absent', 'late_minutes', 'overtime_minutes', 'work_minutes', 'notes', 'import_batch_id', 'updated_at']
                    );
                }
            }

            // تحديث الدفعة
            $batch->update([
                'status'          => ImportStatus::Completed,
                'records_count'   => $totalRecords,
                'employees_count' => $totalEmployees,
            ]);

            DB::commit();

            return $batch->fresh();

        } catch (\Exception $e) {
            DB::rollBack();

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
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ===================================================
    // مساعدات خاصة
    // ===================================================

    /**
     * حذف البيانات القديمة لنفس الشهر (عند الاستبدال)
     */
    private function deleteExistingDataForMonth(int $month, int $year, int $excludeBatchId): void
    {
        $existingBatches = ImportBatch::where('month', $month)
            ->where('year', $year)
            ->where('id', '!=', $excludeBatchId)
            ->get();

        foreach ($existingBatches as $oldBatch) {
            AttendanceRecord::where('import_batch_id', $oldBatch->id)->delete();
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
        $defaults = [
            'work_start_time'     => Setting::getValue('work_start_time', '09:00'),
            'work_end_time'       => Setting::getValue('work_end_time', '17:00'),
            'overtime_start_time' => Setting::getValue('overtime_start_time', '17:30'),
            'late_grace_minutes'  => (int) Setting::getValue('late_grace_minutes', 30),
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
}
