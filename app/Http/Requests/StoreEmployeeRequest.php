<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'ac_no'               => ['required', 'string', 'max:50', 'unique:employees,ac_no'],
            'name'                => ['required', 'string', 'max:255'],
            'basic_salary'        => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'work_start_time'     => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'work_end_time'       => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'overtime_start_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'late_grace_minutes'  => ['nullable', 'integer', 'min:0', 'max:240'],
        ];
    }

    public function messages(): array
    {
        return [
            'ac_no.required'             => 'رقم الموظف (AC-No) مطلوب.',
            'ac_no.unique'               => 'رقم الموظف هذا موجود بالفعل في النظام.',
            'ac_no.max'                  => 'رقم الموظف لا يتجاوز 50 حرفاً.',
            'name.required'              => 'اسم الموظف مطلوب.',
            'name.max'                   => 'اسم الموظف لا يتجاوز 255 حرفاً.',
            'basic_salary.required'      => 'المرتب الأساسي مطلوب.',
            'basic_salary.numeric'       => 'المرتب الأساسي يجب أن يكون رقماً.',
            'basic_salary.min'           => 'المرتب الأساسي لا يمكن أن يكون سالباً.',
            'work_start_time.regex'      => 'صيغة وقت الحضور غير صحيحة (HH:MM).',
            'work_end_time.regex'        => 'صيغة وقت الانصراف غير صحيحة (HH:MM).',
            'overtime_start_time.regex'  => 'صيغة وقت بدء الأوفرتايم غير صحيحة (HH:MM).',
            'late_grace_minutes.integer' => 'مدة السماح يجب أن تكون رقماً صحيحاً.',
            'late_grace_minutes.min'     => 'مدة السماح لا تكون سالبة.',
            'late_grace_minutes.max'     => 'مدة السماح لا تتجاوز 240 دقيقة.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ac_no'               => 'رقم الموظف',
            'name'                => 'اسم الموظف',
            'basic_salary'        => 'المرتب الأساسي',
            'work_start_time'     => 'وقت الحضور',
            'work_end_time'       => 'وقت الانصراف',
            'overtime_start_time' => 'وقت بدء الأوفرتايم',
            'late_grace_minutes'  => 'مدة السماح بالتأخير',
        ];
    }
}
