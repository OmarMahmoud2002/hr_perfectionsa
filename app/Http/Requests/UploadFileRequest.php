<?php

namespace App\Http\Requests;

use App\Services\Excel\ExcelReaderService;
use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',
                // نتحقق من الامتداد فقط لأن PHP/finfo قد يُعيد نوع MIME مختلف
                // لملفات Excel 97-2003 (.xls) على أنظمة Windows
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (!in_array($ext, ['xlsx', 'xls'])) {
                        $fail('يجب أن يكون الملف بصيغة Excel (.xlsx أو .xls).');
                        return;
                    }
                    // قائمة أنواع MIME المقبولة لملفات Excel فقط
                    // تم إزالة application/octet-stream و application/zip لأسباب أمنية
                    $allowedMimes = [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                        'application/vnd.ms-excel',                                           // .xls
                        'application/msexcel',
                        'application/x-msexcel',
                        'application/x-ms-excel',
                        'application/x-excel',
                        'application/x-dos_ms_excel',
                        'application/xls',
                        'application/x-xls',
                    ];
                    $detectedMime = $value->getMimeType();
                    if (!in_array($detectedMime, $allowedMimes)) {
                        $fail('يجب أن يكون الملف بصيغة Excel (.xlsx أو .xls). (النوع المكتشف: ' . $detectedMime . ')');
                    }

                    // التحقق من محتوى الملف: محاولة قراءة الملف للتأكد من أنه ملف Excel صالح
                    try {
                        $reader = app(ExcelReaderService::class);
                        $rows = $reader->readUploadedFile($value);

                        // التحقق من أن الملف يحتوي على بيانات
                        if (empty($rows) || !is_array($rows)) {
                            $fail('الملف فارغ أو تالف. يرجى رفع ملف Excel صالح.');
                        }
                    } catch (\Exception $e) {
                        $fail('فشل في قراءة محتوى الملف. يرجى التأكد من أن الملف هو Excel صالح وغير تالف.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'يرجى اختيار ملف للرفع.',
            'file.file'     => 'يجب أن يكون الحقل ملفاً.',
            'file.max'      => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
        ];
    }
}
