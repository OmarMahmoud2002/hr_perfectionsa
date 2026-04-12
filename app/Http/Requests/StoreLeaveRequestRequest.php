<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return in_array($user->role, ['employee', 'office_girl', 'department_manager', 'hr', 'admin', 'manager'], true)
            && $user->employee_id !== null;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'يرجى اختيار تاريخ بداية الإجازة.',
            'start_date.date' => 'تاريخ بداية الإجازة غير صالح.',
            'end_date.required' => 'يرجى اختيار تاريخ نهاية الإجازة.',
            'end_date.date' => 'تاريخ نهاية الإجازة غير صالح.',
            'end_date.after_or_equal' => 'تاريخ نهاية الإجازة يجب أن يكون بعد تاريخ البداية أو مساويًا له.',
            'reason.max' => 'سبب الإجازة يجب ألا يتجاوز 2000 حرف.',
        ];
    }
}
