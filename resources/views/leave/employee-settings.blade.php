@extends('layouts.app')

@section('title', 'إعدادات موظفي الإجازات')
@section('page-title', 'إعدادات موظفي الإجازات')
@section('page-subtitle', 'تحديث بيانات الأهلية والرصيد مباشرة من نفس الجدول')

@section('content')
<div class="space-y-5">
    <div class="card p-0 overflow-hidden relative">
        <div class="absolute inset-0 opacity-95"
             style="background: radial-gradient(circle at 80% 20%, rgba(56,189,248,.24), transparent 35%), radial-gradient(circle at 10% 85%, rgba(245,158,11,.22), transparent 42%), linear-gradient(130deg, #315f8f 0%, #28786f 100%);"></div>
        <div class="relative p-6 sm:p-7 text-white">
            <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-white/70 mb-2">Employee Leave Settings</p>
                    <h2 class="text-2xl sm:text-3xl font-black">لوحة إعدادات الموظفين</h2>
                    <p class="text-sm text-white/85 mt-2">تعديل أهلية طلب الإجازة والرصد السنوي لكل موظف من الصف مباشرة.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('leave.approvals.index') }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold bg-white text-[#2f6f9a] border border-white hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition duration-200">العودة لقرارات الإجازات</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-4">
        <form method="GET" class="flex flex-wrap items-end gap-2.5">
            <div class="min-w-[220px] flex-1">
                <label class="form-label">بحث</label>
                <input type="text" name="search" value="{{ $search }}" class="form-input" placeholder="اسم الموظف أو رقمه">
            </div>
            <div class="w-full sm:w-[220px]">
                <label class="form-label">القسم</label>
                <select name="department_id" class="form-input">
                    <option value="0">كل الأقسام</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ (int) $departmentId === (int) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-[120px]">
                <label class="form-label">السنة</label>
                <input type="number" min="2020" max="2100" name="year" value="{{ $year }}" class="form-input">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary btn-sm">تطبيق الفلتر</button>
                <a href="{{ route('leave.approvals.employee-settings') }}" class="btn-ghost btn-sm">إعادة ضبط</a>
            </div>
        </form>

        <div class="mt-4 p-3 rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-600">
            القيم الافتراضية الحالية: أيام الخدمة قبل الإجازة <strong>{{ $defaultRequiredDays }}</strong> يوم - رصيد سنوي <strong>{{ $defaultAnnualQuota }}</strong> يوم.
        </div>
    </div>

    <form method="POST" action="{{ route('leave.approvals.employee-settings.bulk-update') }}" class="card overflow-hidden" data-loading="true">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">

        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="font-bold text-slate-800">جدول إعدادات الموظفين</h3>
            <button type="submit" class="btn-primary btn-sm">حفظ كل التعديلات</button>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>تاريخ بداية العمل</th>
                        <th>أيام قبل طلب الإجازة</th>
                        <th>أيام متبقية للأهلية</th>
                        <th>الحالة</th>
                        <th>إجمالي رصيد الإجازات</th>
                        <th>المستهلك</th>
                        <th>المتبقي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        @php
                            $snapshot = $employee->eligibility_snapshot ?? [];
                            $daysRemainingToEligibility = (int) ($snapshot['days_remaining_to_eligibility'] ?? 0);
                            $eligible = (bool) ($snapshot['status'] ?? false);
                            $annualQuota = (int) ($snapshot['annual_quota_days'] ?? 0);
                            $usedDays = (int) ($snapshot['used_days'] ?? 0);
                            $remainingDays = (int) ($snapshot['remaining_days'] ?? 0);
                            $profile = $employee->leaveProfile;
                            $rowInput = old('rows.'.$loop->index, []);
                        @endphp
                        <tr>
                            <td>
                                <div>
                                    <p class="font-semibold text-slate-800">{{ $employee->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $employee->position_line }}</p>
                                </div>
                                <input type="hidden" name="rows[{{ $loop->index }}][employee_id]" value="{{ $employee->id }}">
                            </td>
                            <td>
                                <input type="date" name="rows[{{ $loop->index }}][employment_start_date]" value="{{ $rowInput['employment_start_date'] ?? optional($profile?->employment_start_date)->format('Y-m-d') }}" class="form-input !min-w-[170px]">
                            </td>
                            <td>
                                <input type="number" min="0" max="3650" name="rows[{{ $loop->index }}][required_work_days_before_leave]" value="{{ array_key_exists('required_work_days_before_leave', $rowInput) ? $rowInput['required_work_days_before_leave'] : $profile?->required_work_days_before_leave }}" class="form-input !w-28">
                            </td>
                            <td>
                                <span class="font-bold {{ $daysRemainingToEligibility > 0 ? 'text-amber-600' : 'text-emerald-700' }}">{{ $daysRemainingToEligibility }}</span>
                            </td>
                            <td>
                                @if($eligible)
                                    <span class="badge-success">مسموح</span>
                                @else
                                    <span class="badge-warning">غير مسموح</span>
                                @endif
                            </td>
                            <td>
                                <input type="number" min="0" max="365" name="rows[{{ $loop->index }}][annual_leave_quota]" value="{{ array_key_exists('annual_leave_quota', $rowInput) ? $rowInput['annual_leave_quota'] : ($profile?->annual_leave_quota ?? $annualQuota) }}" class="form-input !w-24">
                            </td>
                            <td><span class="font-semibold text-slate-700">{{ $usedDays }}</span></td>
                            <td><span class="font-semibold text-emerald-700">{{ $remainingDays }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-slate-500">لا يوجد موظفون مطابقون للفلاتر الحالية.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $employees->links() }}
        </div>
    </form>
</div>
@endsection
