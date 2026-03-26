<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeMonthVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return in_array($user->role, ['employee', 'admin', 'manager', 'hr'], true);
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
