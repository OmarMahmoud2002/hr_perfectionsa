<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyPerformanceEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isEmployee() === true;
    }

    public function rules(): array
    {
        return [
            'work_date' => ['nullable', 'date', 'date_equals:'.now()->toDateString()],
            'project_name' => ['required', 'string', 'max:255'],
            'work_description' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,txt,zip',
            ],
            'links' => ['nullable', 'array', 'max:10'],
            'links.*' => ['nullable', 'string', 'max:2048', 'url', 'starts_with:http://,https://'],
        ];
    }

    public function messages(): array
    {
        return [
            'work_date.date_equals'  => 'لا يمكن تسجيل الأداء إلا ليوم الحالي فقط.',
            'project_name.required' => 'اسم المشروع مطلوب.',
            'work_description.required' => 'وصف ما تم إنجازه مطلوب.',
            'attachments.array' => 'المرفقات يجب أن تكون في صيغة قائمة ملفات.',
            'attachments.max' => 'الحد الأقصى للمرفقات هو 5 ملفات.',
            'attachments.*.max' => 'حجم الملف الواحد يجب ألا يتجاوز 10 ميجابايت.',
            'attachments.*.mimes' => 'نوع الملف غير مدعوم.',
            'links.array' => 'الروابط يجب أن تكون في صيغة قائمة.',
            'links.max' => 'الحد الأقصى للروابط هو 10 روابط.',
            'links.*.url' => 'أحد الروابط المدخلة غير صالح.',
            'links.*.max' => 'طول الرابط يجب ألا يزيد عن 2048 حرفا.',
            'links.*.starts_with' => 'الرابط يجب أن يبدأ بـ http:// أو https://.',
        ];
    }
}
