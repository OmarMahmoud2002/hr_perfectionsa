<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeMonthVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isEmployee() === true;
    }

    public function rules(): array
    {
        return [
            'voted_employee_id' => ['required', 'integer', 'exists:employees,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'voted_employee_id.required' => 'يرجى اختيار موظف للتصويت.',
            'voted_employee_id.exists' => 'الموظف المختار غير موجود.',
        ];
    }
}
