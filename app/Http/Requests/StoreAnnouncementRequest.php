<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array((string) $this->user()?->role, ['admin', 'manager', 'hr'], true);
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:5000'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'image' => ['nullable', 'image', 'max:3072'],
            'audience_type' => ['required', Rule::in(['all', 'employees', 'departments', 'job_titles'])],
            'employee_ids' => ['exclude_unless:audience_type,employees', 'array', 'min:1'],
            'employee_ids.*' => ['exclude_unless:audience_type,employees', 'integer', 'distinct', Rule::exists('employees', 'id')],
            'department_ids' => ['exclude_unless:audience_type,departments', 'array', 'min:1'],
            'department_ids.*' => ['exclude_unless:audience_type,departments', 'integer', 'distinct', Rule::exists('departments', 'id')],
            'job_title_ids' => ['exclude_unless:audience_type,job_titles', 'array', 'min:1'],
            'job_title_ids.*' => ['exclude_unless:audience_type,job_titles', 'integer', 'distinct', Rule::exists('job_titles', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'رسالة الإشعار مطلوبة.',
            'message.max' => 'رسالة الإشعار طويلة جدًا.',
            'title.max' => 'عنوان الإشعار يجب ألا يتجاوز 120 حرفًا.',
            'link_url.url' => 'رابط التحويل غير صحيح.',
            'link_url.max' => 'رابط التحويل طويل جدًا.',
            'image.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 3 ميجابايت.',
            'audience_type.required' => 'اختر طريقة تحديد المستلمين.',
            'audience_type.in' => 'طريقة تحديد المستلمين غير صالحة.',
            'employee_ids.min' => 'اختر موظفًا واحدًا على الأقل.',
            'employee_ids.*.exists' => 'أحد الموظفين المختارين غير موجود.',
            'department_ids.min' => 'اختر قسمًا واحدًا على الأقل.',
            'department_ids.*.exists' => 'أحد الأقسام المختارة غير موجود.',
            'job_title_ids.min' => 'اختر وظيفة واحدة على الأقل.',
            'job_title_ids.*.exists' => 'إحدى الوظائف المختارة غير موجودة.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalizeIds = static fn ($values) => collect((array) $values)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        $linkUrl = trim((string) $this->input('link_url', ''));
        if ($linkUrl !== '' && ! preg_match('#^https?://#i', $linkUrl)) {
            $linkUrl = 'https://' . ltrim($linkUrl, '/');
        }

        $this->merge([
            'title' => trim((string) $this->input('title', '')) ?: null,
            'message' => trim((string) $this->input('message', '')),
            'link_url' => $linkUrl !== '' ? $linkUrl : null,
            'employee_ids' => $normalizeIds($this->input('employee_ids', [])),
            'department_ids' => $normalizeIds($this->input('department_ids', [])),
            'job_title_ids' => $normalizeIds($this->input('job_title_ids', [])),
        ]);
    }
}
