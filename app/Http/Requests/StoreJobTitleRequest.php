<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminLike() ?? false;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:150'],
            'system_role_mapping' => ['nullable', 'string', 'in:employee,user,office_girl,hr,manager,admin'],
            'is_active' => ['nullable', 'boolean'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'submit_action' => ['nullable', 'string', 'in:save,save_and_add_new'],
        ];
    }
}
