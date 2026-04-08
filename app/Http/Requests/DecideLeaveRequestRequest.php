<?php

namespace App\Http\Requests;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return in_array($user->role, ['admin', 'manager', 'hr', 'department_manager'], true);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'partially_approved', 'rejected'])],
            'approved_days' => ['nullable', 'integer', 'min:1'],
            'approved_dates' => ['nullable', 'array'],
            'approved_dates.*' => ['date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $decision = $this->input('decision');
            $user = $this->user();
            /** @var LeaveRequest|null $leaveRequest */
            $leaveRequest = $this->route('leaveRequest');

            if ($decision === 'partially_approved') {
                if (!in_array($user?->role, ['admin', 'manager', 'hr'], true)) {
                    $validator->errors()->add('decision', 'الموافقة الجزئية متاحة للـ HR والإدارة فقط.');
                }

                $approvedDates = collect((array) $this->input('approved_dates', []))
                    ->filter(fn ($date) => is_string($date) && $date !== '')
                    ->values();

                if ($approvedDates->isNotEmpty()) {
                    $normalized = $approvedDates
                        ->unique()
                        ->values();

                    if ($normalized->count() !== $approvedDates->count()) {
                        $validator->errors()->add('approved_dates', 'لا يمكن تكرار نفس التاريخ في الاعتماد الجزئي.');
                    }

                    if ($leaveRequest !== null) {
                        $startDate = $leaveRequest->start_date?->format('Y-m-d');
                        $endDate = $leaveRequest->end_date?->format('Y-m-d');

                        foreach ($normalized as $date) {
                            try {
                                $candidate = Carbon::parse((string) $date)->format('Y-m-d');
                            } catch (\Throwable $e) {
                                $validator->errors()->add('approved_dates', 'أحد التواريخ المحددة غير صالح.');
                                continue;
                            }

                            if ($startDate !== null && $endDate !== null && ($candidate < $startDate || $candidate > $endDate)) {
                                $validator->errors()->add('approved_dates', 'كل التواريخ المعتمدة جزئيًا يجب أن تكون داخل فترة الطلب الأصلية.');
                                break;
                            }
                        }

                        if ($normalized->count() >= (int) $leaveRequest->requested_days) {
                            $validator->errors()->add('approved_dates', 'الاعتماد الجزئي يجب أن يكون أقل من الأيام المطلوبة في الطلب.');
                        }
                    }
                }

                if ($approvedDates->isEmpty() && (int) $this->input('approved_days', 0) <= 0) {
                    $validator->errors()->add('approved_days', 'يرجى إدخال عدد أيام الموافقة الجزئية.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'يرجى اختيار القرار.',
            'decision.in' => 'القرار المختار غير صالح.',
            'approved_days.integer' => 'عدد الأيام يجب أن يكون رقمًا صحيحًا.',
            'approved_days.min' => 'عدد الأيام يجب أن يكون يومًا واحدًا على الأقل.',
            'approved_dates.array' => 'تنسيق التواريخ المختارة غير صالح.',
            'approved_dates.*.date_format' => 'صيغة أحد التواريخ المختارة غير صحيحة.',
            'note.max' => 'الملاحظة يجب ألا تتجاوز 2000 حرف.',
        ];
    }
}
