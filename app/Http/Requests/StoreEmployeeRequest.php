<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdminLike();
    }

    public function rules(): array
    {
        return [
            'ac_no'               => ['required', 'string', 'max:50', 'unique:employees,ac_no'],
            'name'                => ['required', 'string', 'max:255'],
            'job_title_id'        => ['nullable', 'integer', 'exists:job_titles,id', 'required_without:job_title'],
            'job_title'           => ['nullable', 'string', 'max:50', 'exists:job_titles,key', 'required_without:job_title_id'],
            'department_id'       => ['nullable', 'integer', 'exists:departments,id'],
            'basic_salary'        => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'is_remote_worker'    => ['required', 'boolean'],
            'work_start_time'     => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'work_end_time'       => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'overtime_start_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'late_grace_minutes'  => ['nullable', 'integer', 'min:0', 'max:240'],
            'location_ids'        => ['nullable', 'array', 'min:1', 'max:2', Rule::requiredIf(fn () => $this->boolean('is_remote_worker'))],
            'location_ids.*'      => ['integer', 'exists:locations,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $locationIds = array_values(array_filter((array) $this->input('location_ids', []), fn ($id) => $id !== null && $id !== ''));

            if (!$this->boolean('is_remote_worker') && count($locationIds) > 0) {
                $validator->errors()->add('location_ids', 'لا يمكن اختيار مواقع إلا إذا كان نمط الدوام ريموت.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'ac_no.required'             => 'رقم الموظف (AC-No) مطلوب.',
            'ac_no.unique'               => 'رقم الموظف هذا موجود بالفعل في النظام.',
            'ac_no.max'                  => 'رقم الموظف لا يتجاوز 50 حرفاً.',
            'name.required'              => 'اسم الموظف مطلوب.',
            'name.max'                   => 'اسم الموظف لا يتجاوز 255 حرفاً.',
            'job_title_id.required'      => 'الوظيفة مطلوبة.',
            'job_title_id.exists'        => 'الوظيفة المختارة غير صالحة.',
            'job_title.required'         => 'الوظيفة مطلوبة.',
            'job_title.exists'           => 'الوظيفة المختارة غير صالحة.',
            'department_id.exists'       => 'القسم المختار غير صالح.',
            'basic_salary.required'      => 'المرتب الأساسي مطلوب.',
            'basic_salary.numeric'       => 'المرتب الأساسي يجب أن يكون رقماً.',
            'basic_salary.min'           => 'المرتب الأساسي لا يمكن أن يكون سالباً.',
            'is_remote_worker.required'  => 'يرجى تحديد نمط دوام الموظف.',
            'is_remote_worker.boolean'   => 'قيمة نمط الدوام غير صحيحة.',
            'work_start_time.regex'      => 'صيغة وقت الحضور غير صحيحة (HH:MM).',
            'work_end_time.regex'        => 'صيغة وقت الانصراف غير صحيحة (HH:MM).',
            'overtime_start_time.regex'  => 'صيغة وقت بدء الأوفرتايم غير صحيحة (HH:MM).',
            'late_grace_minutes.integer' => 'مدة السماح يجب أن تكون رقماً صحيحاً.',
            'late_grace_minutes.min'     => 'مدة السماح لا تكون سالبة.',
            'late_grace_minutes.max'     => 'مدة السماح لا تتجاوز 240 دقيقة.',
            'location_ids.required'      => 'يجب اختيار موقع واحد على الأقل للموظف الريموت.',
            'location_ids.array'         => 'صيغة المواقع المختارة غير صحيحة.',
            'location_ids.min'           => 'يجب اختيار موقع واحد على الأقل للموظف الريموت.',
            'location_ids.max'           => 'يمكن اختيار موقعين كحد أقصى للموظف الريموت.',
            'location_ids.*.exists'      => 'أحد المواقع المختارة غير موجود.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ac_no'               => 'رقم الموظف',
            'name'                => 'اسم الموظف',
            'job_title_id'        => 'الوظيفة',
            'job_title'           => 'الوظيفة',
            'department_id'       => 'القسم',
            'basic_salary'        => 'المرتب الأساسي',
            'is_remote_worker'    => 'نمط الدوام ريموت',
            'work_start_time'     => 'وقت الحضور',
            'work_end_time'       => 'وقت الانصراف',
            'overtime_start_time' => 'وقت بدء الأوفرتايم',
            'late_grace_minutes'  => 'مدة السماح بالتأخير',
            'location_ids'        => 'المواقع المسموح بها',
            'location_ids.*'      => 'الموقع المحدد',
        ];
    }
}
