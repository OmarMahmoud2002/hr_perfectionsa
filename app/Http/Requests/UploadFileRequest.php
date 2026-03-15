<?php

namespace App\Http\Requests;

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
                    // قائمة شاملة بأنواع MIME المقبولة لملفات Excel
                    $allowedMimes = [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'application/msexcel',
                        'application/x-msexcel',
                        'application/x-ms-excel',
                        'application/x-excel',
                        'application/x-dos_ms_excel',
                        'application/xls',
                        'application/x-xls',
                        'application/octet-stream',
                        'application/zip',
                    ];
                    $detectedMime = $value->getMimeType();
                    if (!in_array($detectedMime, $allowedMimes)) {
                        $fail('يجب أن يكون الملف بصيغة Excel (.xlsx أو .xls). (النوع المكتشف: ' . $detectedMime . ')');
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
