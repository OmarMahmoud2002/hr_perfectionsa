<?php

namespace App\Http\Controllers;

use App\Enums\ImportStatus;
use App\Http\Requests\UploadFileRequest;
use App\Models\ImportBatch;
use App\Services\Import\ImportService;
use App\Services\Notifications\EmailNotificationService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportService $importService,
        private readonly EmailNotificationService $emailNotificationService,
    ) {}

    public function showForm()
    {
        $batches = ImportBatch::with('uploader')
            ->latest()
            ->paginate(10);

        return view('import.upload', compact('batches'));
    }

    public function upload(UploadFileRequest $request)
    {
        try {
            $result = $this->importService->uploadAndPreview(
                $request->file('file'),
                auth()->id()
            );

            return redirect()
                ->route('import.confirm.show', $result['batch']->id)
                ->with('success', 'تم رفع الملف وتحليله بنجاح.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'فشل في رفع الملف: ' . $e->getMessage());
        }
    }

    public function showConfirm(ImportBatch $batch)
    {
        if ($batch->status === ImportStatus::Completed) {
            return redirect()->route('import.form')
                ->with('info', 'هذه الدفعة مكتملة بالفعل.');
        }

        $batch->load('publicHolidays');

        $allSettings = \App\Models\Setting::getAllAsArray();

        $defaultSettings = [
            'work_start_time' => $allSettings['work_start_time'] ?? '09:00',
            'work_end_time' => $allSettings['work_end_time'] ?? '17:00',
            'overtime_start_time' => $allSettings['overtime_start_time'] ?? '17:30',
            'late_grace_minutes' => $allSettings['late_grace_minutes'] ?? '30',
        ];

        $batchSettings = $batch->import_settings ?? [];

        $existingBatch = ImportBatch::where('month', $batch->month)
            ->where('year', $batch->year)
            ->where('status', ImportStatus::Completed)
            ->where('id', '!=', $batch->id)
            ->first();

        return view('import.confirm', compact('batch', 'defaultSettings', 'batchSettings', 'existingBatch'));
    }

    public function confirm(Request $request, ImportBatch $batch)
    {
        if ($batch->status === ImportStatus::Completed) {
            return redirect()->route('import.form')
                ->with('error', 'هذه الدفعة مكتملة بالفعل ولا يمكن إعادة استيرادها.');
        }

        $request->validate([
            'work_start_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'work_end_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'overtime_start_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'late_grace_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'replace_existing' => ['nullable', 'boolean'],
        ], [
            'work_start_time.regex' => 'صيغة وقت بدء العمل غير صحيحة (HH:MM).',
            'work_end_time.regex' => 'صيغة وقت انتهاء العمل غير صحيحة (HH:MM).',
            'overtime_start_time.regex' => 'صيغة وقت بدء الـ Overtime غير صحيحة (HH:MM).',
        ]);

        $importSettings = array_filter([
            'work_start_time' => $request->work_start_time,
            'work_end_time' => $request->work_end_time,
            'overtime_start_time' => $request->overtime_start_time,
            'late_grace_minutes' => $request->late_grace_minutes,
        ], fn ($v) => $v !== null && $v !== '');

        $replaceExisting = $request->boolean('replace_existing', false);

        try {
            $completed = $this->importService->processImport($batch, $importSettings, $replaceExisting);
            $this->emailNotificationService->notifyAttendanceMonthImported((int) $completed->month, (int) $completed->year);

            return redirect()->route('import.form')
                ->with('success', "تم استيراد بيانات شهر {$completed->month_name} {$completed->year} بنجاح. ({$completed->records_count} سجل، {$completed->employees_count} موظف)");
        } catch (\Exception $e) {
            return redirect()->route('import.confirm.show', $batch->id)
                ->with('error', 'فشل في الاستيراد: ' . $e->getMessage());
        }
    }

    public function destroy(ImportBatch $batch)
    {
        try {
            $label = "{$batch->month_name} {$batch->year}";
            $this->importService->deleteBatch($batch);

            return redirect()->route('import.form')
                ->with('success', "تم حذف بيانات شهر {$label} بنجاح.");
        } catch (\Exception $e) {
            return back()->with('error', 'فشل في الحذف: ' . $e->getMessage());
        }
    }

    public function history()
    {
        $batches = ImportBatch::with(['uploader', 'publicHolidays'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(20);

        return view('import.history', compact('batches'));
    }
}
