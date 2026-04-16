<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdminLike();
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')->id;

        return [
            'ac_no'               => ['required', 'string', 'max:50', "unique:employees,ac_no,{$employeeId}"],
            'name'                => ['required', 'string', 'max:255'],
            'job_title_id'        => ['nullable', 'integer', 'exists:job_titles,id', 'required_without:job_title'],
            'job_title'           => ['nullable', 'string', 'max:50', 'exists:job_titles,key', 'required_without:job_title_id'],
            'department_id'       => ['nullable', 'integer', 'exists:departments,id'],
            'employment_start_date' => ['nullable', 'date'],
            'basic_salary'        => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'is_remote_worker'    => ['required', 'boolean'],
            'allow_remote_from_anywhere' => ['nullable', 'boolean'],
            'work_start_time'     => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'work_end_time'       => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'overtime_start_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'late_grace_minutes'  => ['nullable', 'integer', 'min:0', 'max:240'],
            'location_ids'        => ['nullable', 'array', 'min:1', 'max:2', Rule::requiredIf(fn () => $this->boolean('is_remote_worker') && !$this->boolean('allow_remote_from_anywhere'))],
            'location_ids.*'      => ['integer', 'exists:locations,id'],
            'remote_allowed_dates' => ['nullable', 'array', 'max:62'],
            'remote_allowed_dates.*' => ['date_format:Y-m-d', 'distinct'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $locationIds = array_values(array_filter((array) $this->input('location_ids', []), fn ($id) => $id !== null && $id !== ''));
            $remoteAllowedDates = array_values(array_filter((array) $this->input('remote_allowed_dates', []), fn ($date) => $date !== null && $date !== ''));

            if (!$this->boolean('is_remote_worker') && count($locationIds) > 0) {
                $validator->errors()->add('location_ids', 'لا يمكن اختيار مواقع إلا إذا كان نمط الدوام اونلاين.');
            }

            if (!$this->boolean('is_remote_worker') && count($remoteAllowedDates) > 0) {
                $validator->errors()->add('remote_allowed_dates', 'لا يمكن تحديد أيام الاونلاين إلا إذا كان نمط الدوام اونلاين.');
            }

            if (!$this->boolean('is_remote_worker') && $this->boolean('allow_remote_from_anywhere')) {
                $validator->errors()->add('allow_remote_from_anywhere', 'خيار غير مقيد بمكان متاح فقط عند تفعيل الدوام الاونلاين.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'ac_no.required'             => 'رقم الموظف (AC-No) مطلوب.',
            'ac_no.unique'               => 'رقم الموظف هذا موجود بالفعل لموظف آخر.',
            'ac_no.max'                  => 'رقم الموظف لا يتجاوز 50 حرفاً.',
            'name.required'              => 'اسم الموظف مطلوب.',
            'name.max'                   => 'اسم الموظف لا يتجاوز 255 حرفاً.',
            'job_title_id.required'      => 'الوظيفة مطلوبة.',
            'job_title_id.exists'        => 'الوظيفة المختارة غير صالحة.',
            'job_title.required'         => 'الوظيفة مطلوبة.',
            'job_title.exists'           => 'الوظيفة المختارة غير صالحة.',
            'department_id.exists'       => 'القسم المختار غير صالح.',
            'employment_start_date.date' => 'تاريخ بداية العمل غير صالح.',
            'basic_salary.required'      => 'المرتب الأساسي مطلوب.',
            'basic_salary.numeric'       => 'المرتب الأساسي يجب أن يكون رقماً.',
            'basic_salary.min'           => 'المرتب الأساسي لا يمكن أن يكون سالباً.',
            'is_remote_worker.required'  => 'يرجى تحديد نمط دوام الموظف.',
            'is_remote_worker.boolean'   => 'قيمة نمط الدوام غير صحيحة.',
            'allow_remote_from_anywhere.boolean' => 'قيمة خيار غير مقيد بمكان غير صحيحة.',
            'work_start_time.regex'      => 'صيغة وقت الحضور غير صحيحة (HH:MM).',
            'work_end_time.regex'        => 'صيغة وقت الانصراف غير صحيحة (HH:MM).',
            'overtime_start_time.regex'  => 'صيغة وقت بدء الأوفرتايم غير صحيحة (HH:MM).',
            'late_grace_minutes.integer' => 'مدة السماح يجب أن تكون رقماً صحيحاً.',
            'late_grace_minutes.min'     => 'مدة السماح لا تكون سالبة.',
            'late_grace_minutes.max'     => 'مدة السماح لا تتجاوز 240 دقيقة.',
            'location_ids.required'      => 'يجب اختيار موقع واحد على الأقل للموظف الاونلاين.',
            'location_ids.array'         => 'صيغة المواقع المختارة غير صحيحة.',
            'location_ids.min'           => 'يجب اختيار موقع واحد على الأقل للموظف الاونلاين.',
            'location_ids.max'           => 'يمكن اختيار موقعين كحد أقصى للموظف الاونلاين.',
            'location_ids.*.exists'      => 'أحد المواقع المختارة غير موجود.',
            'remote_allowed_dates.array' => 'صيغة أيام الاونلاين المختارة غير صحيحة.',
            'remote_allowed_dates.max' => 'يمكن حفظ 62 يوماً كحد أقصى في كل مرة.',
            'remote_allowed_dates.*.date_format' => 'صيغة يوم الاونلاين يجب أن تكون YYYY-MM-DD.',
            'remote_allowed_dates.*.distinct' => 'تم تكرار أحد أيام الاونلاين أكثر من مرة.',
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
            'employment_start_date' => 'تاريخ بداية العمل',
            'basic_salary'        => 'المرتب الأساسي',
            'is_remote_worker'    => 'نمط الدوام اونلاين',
            'allow_remote_from_anywhere' => 'غير مقيد بمكان',
            'work_start_time'     => 'وقت الحضور',
            'work_end_time'       => 'وقت الانصراف',
            'overtime_start_time' => 'وقت بدء الأوفرتايم',
            'late_grace_minutes'  => 'مدة السماح بالتأخير',
            'location_ids'        => 'المواقع المسموح بها',
            'location_ids.*'      => 'الموقع المحدد',
            'remote_allowed_dates' => 'أيام الدوام الاونلاين',
            'remote_allowed_dates.*' => 'يوم الدوام الاونلاين',
        ];
    }
}
