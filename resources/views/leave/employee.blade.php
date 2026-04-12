@extends('layouts.app')

@section('title', 'طلبات الإجازة')
@section('page-title', 'طلب إجازة')
@section('page-subtitle', 'إدارة رصيد الإجازات والأهلية والطلبات السابقة')

@section('content')
@php
    $eligibilityReasonLabel = [
        'ok' => 'مؤهل لتقديم طلب إجازة',
        'minimum_service_not_reached' => 'لم تكتمل مدة الخدمة المطلوبة بعد',
        'employment_start_date_missing' => 'يرجى استكمال تاريخ بداية العمل في ملفك الوظيفي',
    ][$eligibility['reason']] ?? 'حالة الأهلية غير معروفة';

    $statusBadge = [
        'pending' => 'badge-warning',
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
    ];

    $statusLabel = [
        'pending' => 'قيد المراجعة',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
    ];
@endphp

<div class="space-y-5" x-data="leaveFormPage()">
    <div class="card p-0 overflow-hidden relative animate-fade-in">
        <div class="absolute inset-0 opacity-95"
             style="background: radial-gradient(circle at 80% 20%, rgba(245,158,11,.20), transparent 35%), radial-gradient(circle at 12% 88%, rgba(16,185,129,.25), transparent 42%), linear-gradient(130deg, #2e6d98 0%, #2f7c77 100%);"></div>
        <div class="relative p-6 sm:p-7 text-white">
            <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-white/70 mb-2">Leave Requests</p>
                    <h2 class="text-2xl sm:text-3xl font-black">خطط إجازتك بثقة ووضوح</h2>
                    <p class="text-sm text-white/85 mt-2">النظام يحسب الأهلية والرصيد تلقائيًا ويعرض حالة الاعتماد لكل طرف بشكل فوري.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full xl:w-auto">
                    <div class="rounded-xl border border-white/20 bg-white/10 p-3 sm:min-w-[130px]">
                        <p class="text-[11px] text-white/75">الرصيد السنوي</p>
                        <p class="text-xl font-black mt-1">{{ (int) $balance->annual_quota_days }}</p>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 p-3 sm:min-w-[130px]">
                        <p class="text-[11px] text-white/75">المستخدم</p>
                        <p class="text-xl font-black mt-1">{{ (int) $balance->used_days }}</p>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 p-3 sm:min-w-[130px]">
                        <p class="text-[11px] text-white/75">المتبقي الحالي</p>
                        <p class="text-xl font-black mt-1">{{ (int) $balance->remaining_days }} يوم</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 space-y-5">
            <div class="card overflow-hidden animate-slide-up" style="animation-delay:60ms; animation-fill-mode:both;">
                <div class="card-header">
                    <h3 class="card-header-title">الأهلية والرصيد</h3>
                    <span class="card-header-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5-2a9 9 0 11-18 0 9 9 0 0118 0"/>
                        </svg>
                    </span>
                </div>
                <div class="p-5">
                    <div class="rounded-2xl border p-4 {{ ($eligibility['eligible'] ?? false) ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }}">
                        <p class="font-bold {{ ($eligibility['eligible'] ?? false) ? 'text-emerald-700' : 'text-amber-700' }}">{{ $eligibilityReasonLabel }}</p>
                        @if(!($eligibility['eligible'] ?? false))
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3 text-sm">
                                <div>
                                    <p class="text-slate-500 text-xs">أيام الخدمة</p>
                                    <p class="font-bold text-slate-800">{{ (int) ($eligibility['service_days'] ?? 0) }} يوم</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs">الحد الأدنى المطلوب</p>
                                    <p class="font-bold text-slate-800">{{ (int) ($eligibility['required_work_days'] ?? 0) }} يوم</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-xs">المتبقي للأهلية</p>
                                    <p class="font-bold text-slate-800">{{ (int) ($eligibility['days_remaining_to_eligibility'] ?? 0) }} يوم</p>
                                </div>
                            </div>
                        @else
                            <p class="text-xs text-emerald-700 mt-2">يمكنك إرسال الطلب مباشرة، وسيتم عرض الرصيد المتبقي بعد كل اعتماد.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden animate-slide-up" style="animation-delay:100ms; animation-fill-mode:both;">
                <div class="card-header">
                    <h3 class="card-header-title">طلب إجازة جديد</h3>
                    <span class="card-header-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </span>
                </div>
                <div class="p-5">
                    <form action="{{ route('leave.requests.store') }}" method="POST" class="space-y-4" data-loading="true">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="start_date" class="form-label">بداية الإجازة</label>
                                <input type="date" id="start_date" name="start_date" class="form-input"
                                       min="{{ $today }}" value="{{ old('start_date') }}"
                                       x-model="startDate" @change="recalculateDays">
                            </div>
                            <div>
                                <label for="end_date" class="form-label">نهاية الإجازة</label>
                                <input type="date" id="end_date" name="end_date" class="form-input"
                                       :min="endMinDate" value="{{ old('end_date') }}"
                                       x-model="endDate" @change="recalculateDays">
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                            <p class="text-xs text-slate-500">عدد الأيام المطلوبة</p>
                            <p class="text-lg font-black text-slate-800 mt-1" x-text="requestedDaysLabel"></p>
                        </div>

                        <div>
                            <label for="reason" class="form-label">السبب / الملاحظات</label>
                            <textarea id="reason" name="reason" rows="4" class="form-input" placeholder="اكتب سبب الإجازة بشكل واضح...">{{ old('reason') }}</textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <button type="submit" class="btn-primary flex-1 justify-center" {{ ($eligibility['eligible'] ?? false) ? '' : 'disabled' }}>
                                إرسال الطلب
                            </button>
                            <p class="text-xs text-slate-500 flex-1 sm:text-left">يتم حساب الأيام تقويميًا، ولا يمكن تعديل الطلب بعد الرفض ويجب إرسال طلب جديد.</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="card p-5 animate-slide-up" style="animation-delay:130ms; animation-fill-mode:both;">
                <p class="text-xs text-slate-500">قسمك الحالي</p>
                <p class="text-base font-bold text-slate-800 mt-1">{{ $employee->department?->name ?? 'غير محدد' }}</p>
                <p class="text-xs text-slate-500 mt-3">مدير القسم</p>
                <p class="text-sm font-semibold text-slate-700 mt-1">{{ $employee->department?->managerEmployee?->name ?? 'غير متاح' }}</p>
            </div>


        </div>
    </div>

    <div class="card overflow-hidden animate-slide-up" style="animation-delay:190ms; animation-fill-mode:both;">
        <div class="card-header">
            <h3 class="card-header-title">سجل طلبات الإجازة</h3>
            <span class="card-header-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V9l-4-4H9z"/>
                </svg>
            </span>
        </div>
        <div class="p-5 space-y-3">
            @forelse($leaveRequests as $leave)
                <div class="rounded-2xl border border-slate-200 p-4 bg-white shadow-sm">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div>
                            <p class="text-sm text-slate-500">{{ $leave->start_date?->format('Y-m-d') }} ← {{ $leave->end_date?->format('Y-m-d') }}</p>
                            <p class="font-bold text-slate-800 mt-1">{{ (int) $leave->requested_days }} يوم مطلوب</p>
                            @if($leave->final_approved_days)
                                <p class="text-xs text-emerald-700 mt-1">المعتمد نهائيًا: {{ (int) $leave->final_approved_days }} يوم</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="{{ $statusBadge[$leave->status] ?? 'badge-gray' }}">{{ $statusLabel[$leave->status] ?? $leave->status }}</span>
                            <span class="badge-gray">HR: {{ $statusLabel[$leave->hr_status] ?? $leave->hr_status }}</span>
                            @if($leave->manager_status !== 'not_required')
                                <span class="badge-gray">المدير: {{ $statusLabel[$leave->manager_status] ?? $leave->manager_status }}</span>
                            @endif
                        </div>
                    </div>
                    @if($leave->reason)
                        <p class="text-sm text-slate-600 mt-3">{{ $leave->reason }}</p>
                    @endif
                    @php
                        $approvalNotes = $leave->approvals->filter(function ($approval) {
                            return filled($approval->note);
                        });
                    @endphp
                    @if($approvalNotes->isNotEmpty())
                        <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2">
                            <p class="text-xs font-bold text-slate-700">ملاحظات قرارات الاعتماد</p>
                            @foreach($approvalNotes as $approval)
                                <div class="text-xs text-slate-600">
                                    <span class="font-semibold text-slate-700">{{ $approval->actor_role === 'hr' ? 'HR' : 'مدير القسم' }}</span>
                                    <span class="text-slate-500"> - {{ $statusLabel[$approval->decision] ?? $approval->decision }}</span>
                                    <p class="mt-1">{{ $approval->note }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-10 text-slate-500">لا توجد طلبات إجازة سابقة حتى الآن.</div>
            @endforelse

            <div>{{ $leaveRequests->links() }}</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function leaveFormPage() {
    return {
        startDate: '{{ old("start_date") }}',
        endDate: '{{ old("end_date") }}',
        requestedDays: 0,

        init() {
            this.syncEndDateWithStart();
            this.recalculateDays();
        },

        syncEndDateWithStart() {
            if (!this.startDate) {
                return;
            }

            if (!this.endDate || this.endDate < this.startDate) {
                this.endDate = this.startDate;
            }
        },

        recalculateDays() {
            this.syncEndDateWithStart();

            if (!this.startDate || !this.endDate) {
                this.requestedDays = 0;
                return;
            }

            const start = new Date(this.startDate + 'T00:00:00');
            const end = new Date(this.endDate + 'T00:00:00');

            if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) {
                this.requestedDays = 0;
                return;
            }

            const diff = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
            this.requestedDays = Math.max(0, diff);
        },

        get requestedDaysLabel() {
            return this.requestedDays > 0 ? `${this.requestedDays} يوم` : '—';
        },

        get endMinDate() {
            if (this.startDate && this.startDate > '{{ $today }}') {
                return this.startDate;
            }

            return '{{ $today }}';
        }
    }
}
</script>
@endpush
