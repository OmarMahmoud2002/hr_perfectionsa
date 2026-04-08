<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminLike() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', 'unique:departments,name'],
            'manager_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
