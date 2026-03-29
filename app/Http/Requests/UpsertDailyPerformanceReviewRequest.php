<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertDailyPerformanceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role;

        return in_array($role, ['admin', 'manager', 'hr', 'user'], true);
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'التقييم مطلوب.',
            'rating.between' => 'التقييم يجب أن يكون بين 1 و 5 نجوم.',
            'comment.max' => 'التعليق يجب ألا يتجاوز 2000 حرف.',
        ];
    }
}
