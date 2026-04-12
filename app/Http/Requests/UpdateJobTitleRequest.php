<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminLike() ?? false;
    }

    public function rules(): array
    {
        $jobTitleId = (int) $this->route('jobTitle')->id;

        return [
            'key' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', Rule::unique('job_titles', 'key')->ignore($jobTitleId)],
            'name_ar' => ['required', 'string', 'max:150'],
            'system_role_mapping' => ['nullable', 'string', 'in:employee,user,office_girl,hr,manager,admin'],
            'is_active' => ['nullable', 'boolean'],
            'manage_employee_assignments' => ['nullable', 'boolean'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'confirm_reassignment' => ['nullable', 'boolean'],
        ];
    }
}
