<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminLike() ?? false;
    }

    public function rules(): array
    {
        $departmentId = (int) $this->route('department')->id;

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('departments', 'name')->ignore($departmentId)],
            'manager_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
