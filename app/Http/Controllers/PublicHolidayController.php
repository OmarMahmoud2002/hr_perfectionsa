<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Models\PublicHoliday;
use App\Services\Attendance\PublicHolidayService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class PublicHolidayController extends Controller
{
    public function __construct(private readonly PublicHolidayService $holidayService) {}

    public function store(Request $request, ImportBatch $batch)
    {
        $request->validate([
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:191'],
        ], [
            'date.required' => 'يرجى تحديد تاريخ الإجازة.',
            'date.date'     => 'صيغة التاريخ غير صحيحة.',
            'name.required' => 'يرجى كتابة اسم الإجازة.',
            'name.max'      => 'اسم الإجازة طويل جداً (الحد الأقصى 191 حرف).',
        ]);

        try {
            $this->holidayService->addHoliday($batch, $request->date, $request->name);

            return redirect()
                ->route('import.confirm.show', $batch->id)
                ->with('success', "تمت إضافة إجازة \"{$request->name}\" بنجاح.");

        } catch (UniqueConstraintViolationException $e) {
            return redirect()
                ->route('import.confirm.show', $batch->id)
                ->withInput()
                ->with('error', "التاريخ {$request->date} مضاف بالفعل كإجازة رسمية لهذا الشهر.");

        } catch (\Exception $e) {
            return redirect()
                ->route('import.confirm.show', $batch->id)
                ->withInput()
                ->with('error', 'فشل في إضافة الإجازة: ' . $e->getMessage());
        }
    }

    public function destroy(ImportBatch $batch, PublicHoliday $holiday)
    {
        // التأكد أن الإجازة تنتمي للدفعة
        if ($holiday->import_batch_id !== $batch->id) {
            abort(404);
        }

        try {
            $this->holidayService->removeHoliday($holiday);

            return redirect()
                ->route('import.confirm.show', $batch->id)
                ->with('success', 'تم حذف الإجازة بنجاح.');

        } catch (\Exception $e) {
            return redirect()
                ->route('import.confirm.show', $batch->id)
                ->with('error', 'فشل في حذف الإجازة: ' . $e->getMessage());
        }
    }
}

