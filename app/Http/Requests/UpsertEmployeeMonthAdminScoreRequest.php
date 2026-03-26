<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertEmployeeMonthAdminScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminLike() === true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'score' => ['required', 'integer', 'between:1,5'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
