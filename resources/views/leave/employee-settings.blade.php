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
                    <p class="text-sm text-white/85 mt-2">تطبيق سياسة موحدة لأيام الأهلية والرصيد السنوي على جميع الموظفين.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('leave.approvals.index') }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold bg-[#123f5f] text-white border border-[#123f5f] hover:bg-[#0f3550] hover:border-[#0f3550] hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 transition duration-200">العودة لقرارات الإجازات</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-3.5 sm:p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-2 items-end">
            <div class="xl:col-span-5">
                <label class="form-label !mb-1 text-xs">بحث</label>
                <input type="text" name="search" value="{{ $search }}" class="form-input !h-9 !min-h-0 !py-1.5 !text-xs" placeholder="اسم الموظف أو رقمه">
            </div>
            <div class="xl:col-span-3">
                <label class="form-label !mb-1 text-xs">القسم</label>
                <select name="department_id" class="form-input !h-9 !min-h-0 !py-1.5 !text-xs" style="padding-right: 35px;">
                    <option value="0">كل الأقسام</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ (int) $departmentId === (int) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="xl:col-span-2">
                <label class="form-label !mb-1 text-xs">السنة</label>
                <input type="number" min="2020" max="2100" name="year" value="{{ $year }}" class="form-input !h-9 !min-h-0 !py-1.5 !text-xs">
            </div>
            <div class="xl:col-span-2 flex gap-2 xl:justify-end flex-nowrap">
                <button type="submit" class="btn-primary btn-sm whitespace-nowrap !px-2.5">تطبيق</button>
                <a href="{{ route('leave.approvals.employee-settings') }}" class="btn-ghost btn-sm whitespace-nowrap !px-2.5">إعادة ضبط</a>
            </div>
        </form>

        <div class="mt-4 p-3 rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-600">
            الافتراضيات الحالية (من نفس الصفحة): أيام الخدمة قبل الإجازة <strong>{{ $defaultRequiredDays }}</strong> يوم - رصيد سنوي <strong>{{ $defaultAnnualQuota }}</strong> يوم.
            <p class="mt-1 text-[11px] text-slate-400">ملاحظة: خصم الإجازات يُسجَّل على سنة دورة الاستحقاق المرتبطة بتاريخ بداية الإجازة، وقد تختلف عن سنة الفلتر.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('leave.approvals.employee-settings.bulk-update') }}" class="card overflow-hidden" data-loading="true">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">

        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="font-bold text-slate-800">جدول إعدادات الموظفين</h3>
            <button type="submit" class="btn-primary btn-sm">تطبيق على كل الموظفين</button>
        </div>

        <div class="p-4 border-b border-slate-100 bg-slate-50/70">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="form-label">أيام قبل طلب الإجازة (موحّد)</label>
                    <input type="number" min="0" max="3650" name="global_required_work_days_before_leave" value="{{ old('global_required_work_days_before_leave', $defaultRequiredDays) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">إجمالي الرصيد السنوي (موحّد)</label>
                    <input type="number" min="0" max="365" name="global_annual_leave_quota" value="{{ old('global_annual_leave_quota', $defaultAnnualQuota) }}" class="form-input">
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">سيتم تطبيق القيمتين على جميع الموظفين، ولا يمكن تعديل القيم من الجدول الفردي.</p>
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
                            $usedDays = (int) ($snapshot['used_days'] ?? 0);
                            $remainingDays = (int) ($snapshot['remaining_days'] ?? 0);
                            $cycleYear = (int) ($snapshot['cycle_year'] ?? 0);
                            $requiredWorkDays = (int) ($snapshot['required_work_days'] ?? $defaultRequiredDays);
                            $annualQuotaDays = (int) ($snapshot['annual_quota_days'] ?? $defaultAnnualQuota);
                            $cycleEnd = (string) ($snapshot['cycle_end'] ?? '');
                            $profile = $employee->leaveProfile;
                        @endphp
                        <tr>
                            <td>
                                <div>
                                    <p class="font-semibold text-slate-800">{{ $employee->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $employee->position_line }}</p>
                                </div>
                            </td>
                            <td>
                                <span class="font-semibold text-slate-700">{{ optional($profile?->employment_start_date)->format('Y-m-d') ?: 'غير محدد' }}</span>
                            </td>
                            <td>
                                <span class="font-semibold text-slate-700">{{ $requiredWorkDays }}</span>
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
                                <span class="font-semibold text-slate-700">{{ $annualQuotaDays }}</span>
                            </td>
                            <td><span class="font-semibold text-slate-700">{{ $usedDays }}</span></td>
                            <td>
                                <span class="font-semibold text-emerald-700">{{ $remainingDays }}</span>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    رصيد سنة {{ $cycleYear > 0 ? $cycleYear : $year }}
                                    @if($cycleEnd !== '')
                                        - ينتهي {{ $cycleEnd }}
                                    @endif
                                </p>
                            </td>
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
