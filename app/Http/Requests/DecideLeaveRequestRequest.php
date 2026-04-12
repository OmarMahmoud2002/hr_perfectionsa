<?php

namespace App\Http\Requests;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var LeaveRequest|null $leaveRequest */
        $leaveRequest = $this->route('leaveRequest');

        if ($user === null || $leaveRequest === null) {
            return false;
        }

        return $user->can('approve', $leaveRequest);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $decision = $this->input('decision');

            if ($decision === 'approved' || $decision === 'rejected') {
                return;
            }

            $validator->errors()->add('decision', 'القرار المختار غير صالح.');
        });
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'يرجى اختيار القرار.',
            'decision.in' => 'القرار المختار غير صالح.',
            'note.max' => 'الملاحظة يجب ألا تتجاوز 2000 حرف.',
        ];
    }
}
