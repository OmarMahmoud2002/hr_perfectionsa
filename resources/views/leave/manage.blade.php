@extends('layouts.app')

@section('title', 'إدارة طلبات الإجازة')
@section('page-title', 'إدارة طلبات الإجازة')
@section('page-subtitle', 'متابعة قرارات HR ومدير القسم مع حالة كل طلب')

@section('content')
@php
    $statusBadge = [
        'pending' => 'badge-warning',
        'approved' => 'badge-success',
        'partially_approved' => 'badge-info',
        'rejected' => 'badge-danger',
        'not_required' => 'badge-gray',
    ];

    $statusLabel = [
        'pending' => 'قيد المراجعة',
        'approved' => 'معتمد',
        'partially_approved' => 'اعتماد جزئي',
        'rejected' => 'مرفوض',
        'not_required' => 'غير مطلوب',
    ];
@endphp

<div class="space-y-5">
    <div class="card p-0 overflow-hidden relative animate-fade-in">
        <div class="absolute inset-0 opacity-95"
             style="background: radial-gradient(circle at 80% 20%, rgba(56,189,248,.24), transparent 35%), radial-gradient(circle at 10% 85%, rgba(245,158,11,.22), transparent 42%), linear-gradient(130deg, #315f8f 0%, #28786f 100%);"></div>
        <div class="relative p-6 sm:p-7 text-white">
            <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-white/70 mb-2">Leave Review Panel</p>
                    <h2 class="text-2xl sm:text-3xl font-black">مركز قرارات الإجازات</h2>
                    <p class="text-sm text-white/85 mt-2">عرض واضح لحالة كل طرف، مع قرارات سريعة وآمنة من نفس الشاشة.</p>
                    @if($isHrLike)
                        <a href="{{ route('leave.approvals.employee-settings') }}" class="inline-flex items-center gap-2 mt-3 text-xs font-bold px-3 py-2 rounded-xl border border-white/35 bg-white/15 hover:bg-white/30 hover:-translate-y-0.5 active:translate-y-0 transition duration-200">
                            إعدادات الموظفين
                        </a>
                    @endif
                </div>
                <form method="GET" class="flex flex-wrap items-center gap-2 w-full xl:w-auto xl:min-w-[560px]">
                    <select name="status" class="form-input !bg-white/95 !border-white/25 !h-10 !min-h-0 !py-1.5 !text-sm !w-full sm:!w-[170px]" onchange="this.form.submit()">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>كل الحالات</option>
                        <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>قيد المراجعة</option>
                        <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>معتمد</option>
                        <option value="partially_approved" {{ $statusFilter === 'partially_approved' ? 'selected' : '' }}>اعتماد جزئي</option>
                        <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                    </select>
                    <select name="month" class="form-input !bg-white/95 !border-white/25 !h-10 !min-h-0 !py-1.5 !text-sm !w-full sm:!w-[170px]" onchange="this.form.submit()">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ (int) $monthFilter === (int) $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create((int) $yearFilter, $m, 1)->locale('ar')->isoFormat('MMMM') }}
                            </option>
                        @endforeach
                    </select>
                    <input type="number" min="2020" max="2100" name="year" value="{{ $yearFilter }}" class="form-input !bg-white/95 !border-white/25 !h-10 !min-h-0 !py-1.5 !text-sm !w-full sm:!w-[120px]" onchange="this.form.submit()">
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
        <div class="card p-3.5 animate-slide-up" style="animation-delay:50ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">إجمالي الطلبات المعروضة</p>
            <p class="text-xl font-black text-slate-800 mt-1">{{ $leaveRequests->total() }}</p>
        </div>
        <div class="card p-3.5 animate-slide-up" style="animation-delay:90ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">طلبات قيد المراجعة</p>
            <p class="text-xl font-black text-amber-600 mt-1">{{ $leaveRequests->getCollection()->where('status', 'pending')->count() }}</p>
        </div>
        <div class="card p-3.5 animate-slide-up" style="animation-delay:130ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">صلاحية الموافقة الجزئية</p>
            <p class="text-sm font-bold mt-2 {{ $isHrLike ? 'text-emerald-700' : 'text-slate-500' }}">{{ $isHrLike ? 'متاحة لحسابك' : 'غير متاحة لحسابك' }}</p>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($leaveRequests as $leave)
            @php
                $canDecide = false;

                if ($leave->finalized_at === null && !in_array($leave->status, ['approved', 'partially_approved', 'rejected'], true)) {
                    if ($isHrLike && $leave->hr_status === 'pending') {
                        $canDecide = true;
                    }

                    if (! $isHrLike && $leave->manager_status === 'pending' && (int) $leave->manager_employee_id === $actorEmployeeId) {
                        $canDecide = true;
                    }
                }

                $avatar = $leave->employee?->user?->profile?->avatar_path
                    ? route('media.avatar', ['path' => $leave->employee->user->profile->avatar_path])
                    : null;

                $dateOptions = [];
                $cursor = $leave->start_date?->copy();
                $endDate = $leave->end_date?->copy();

                if ($cursor && $endDate) {
                    while ($cursor->lte($endDate)) {
                        $dateOptions[] = [
                            'value' => $cursor->format('Y-m-d'),
                            'label' => $cursor->format('m/d'),
                            'day' => $cursor->locale('ar')->shortDayName,
                        ];

                        $cursor->addDay();
                    }
                }
            @endphp

            <div class="card overflow-hidden animate-slide-up" style="animation-delay:120ms; animation-fill-mode:both;" x-data="partialApprovalPicker(@js($dateOptions))">
                <div class="p-5 border-b border-slate-100 bg-slate-50/70">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-xl overflow-hidden flex items-center justify-center text-white text-sm font-black flex-shrink-0"
                                 style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                                @if($avatar)
                                    <img src="{{ $avatar }}" alt="{{ $leave->employee?->name }}" class="block !w-full !h-full max-w-none object-cover">
                                @else
                                    {{ mb_substr((string) ($leave->employee?->name ?? '—'), 0, 1) }}
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800 truncate">{{ $leave->employee?->name ?? '—' }}</p>
                                <p class="text-xs text-slate-500">{{ $leave->employee?->position_line ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="{{ $statusBadge[$leave->status] ?? 'badge-gray' }}">{{ $statusLabel[$leave->status] ?? $leave->status }}</span>
                            <span class="badge-gray">HR: {{ $statusLabel[$leave->hr_status] ?? $leave->hr_status }}</span>
                            <span class="badge-gray">المدير: {{ $statusLabel[$leave->manager_status] ?? $leave->manager_status }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-5 grid grid-cols-1 xl:grid-cols-3 gap-5">
                    <div class="xl:col-span-2 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                            <div class="rounded-xl border border-slate-200 p-3">
                                <p class="text-xs text-slate-500">الفترة</p>
                                <p class="font-semibold text-slate-700 mt-1">{{ $leave->start_date?->format('Y-m-d') }} ← {{ $leave->end_date?->format('Y-m-d') }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-3">
                                <p class="text-xs text-slate-500">الأيام المطلوبة</p>
                                <p class="font-semibold text-slate-700 mt-1">{{ (int) $leave->requested_days }} يوم</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-3">
                                <p class="text-xs text-slate-500">الأيام المعتمدة</p>
                                <p class="font-semibold text-slate-700 mt-1">{{ $leave->final_approved_days ? ((int) $leave->final_approved_days).' يوم' : '—' }}</p>
                            </div>
                        </div>

                        @if($leave->reason)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs text-slate-500 mb-1">سبب الطلب</p>
                                <p class="text-sm text-slate-700 leading-relaxed">{{ $leave->reason }}</p>
                            </div>
                        @endif

                        @if($leave->approvals->isNotEmpty())
                            <div class="rounded-xl border border-slate-200 p-3">
                                <p class="text-xs text-slate-500 mb-2">سجل القرارات</p>
                                <div class="space-y-2">
                                    @foreach($leave->approvals as $approval)
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-xs border-b border-slate-100 pb-2 last:border-0 last:pb-0">
                                            <p class="text-slate-700 font-semibold">{{ $approval->actor_role === 'hr' ? 'HR' : 'مدير القسم' }} - {{ $approval->actor?->name ?? '—' }}</p>
                                            <p class="text-slate-500">{{ $statusLabel[$approval->decision] ?? $approval->decision }} {{ $approval->approved_days ? '- '.$approval->approved_days.' يوم' : '' }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-3">
                        @if($canDecide)
                            <form action="{{ route('leave.approvals.decide', $leave) }}" method="POST" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 space-y-3" data-loading="true">
                                @csrf

                                <h4 class="text-sm font-bold text-amber-800">اتخاذ قرار</h4>

                                <div>
                                    <label class="form-label !text-xs">القرار</label>
                                    <select name="decision" class="form-input" x-model="decision">
                                        <option value="approved">اعتماد</option>
                                        @if($isHrLike)
                                            <option value="partially_approved">اعتماد جزئي</option>
                                        @endif
                                        <option value="rejected">رفض</option>
                                    </select>
                                </div>

                                @if($isHrLike)
                                    <div x-show="decision === 'partially_approved'" x-transition>
                                        <label class="form-label !text-xs">اختر الأيام المعتمدة جزئيًا</label>
                                        <div class="grid grid-cols-4 sm:grid-cols-5 gap-1.5 rounded-xl border border-amber-200 bg-white p-2 max-h-44 overflow-y-auto">
                                            <template x-for="item in dates" :key="item.value">
                                                <button type="button"
                                                        class="rounded-lg border px-1.5 py-1 text-[11px] leading-tight transition"
                                                        :class="isSelected(item.value)
                                                            ? 'border-emerald-300 bg-emerald-50 text-emerald-700 font-semibold'
                                                            : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100'"
                                                        @click="toggleDate(item.value)">
                                                    <span x-text="item.day"></span>
                                                    <span class="block" x-text="item.label"></span>
                                                </button>
                                            </template>
                                        </div>
                                        <div class="mt-2 flex items-center justify-between text-[11px]">
                                            <p class="text-slate-600">تم اختيار <span class="font-bold text-emerald-700" x-text="selectedDates.length"></span> يوم</p>
                                            <button type="button" class="text-slate-500 hover:text-slate-700" @click="clearDates()">مسح التحديد</button>
                                        </div>
                                        <template x-for="selectedDate in selectedDates" :key="'hidden-' + selectedDate">
                                            <input type="hidden" name="approved_dates[]" :value="selectedDate">
                                        </template>
                                        <input type="hidden" name="approved_days" :value="selectedDates.length">
                                        <p class="text-[11px] text-slate-500 mt-1">اختر أيامًا أقل من الأيام المطلوبة ({{ (int) $leave->requested_days }}).</p>
                                    </div>
                                @endif

                                <div>
                                    <label class="form-label !text-xs">ملاحظة (اختياري)</label>
                                    <textarea name="note" rows="3" class="form-input" placeholder="أضف توضيحًا مختصرًا للقرار..."></textarea>
                                </div>

                                <button type="submit" class="btn-gold w-full justify-center">حفظ القرار</button>
                            </form>
                        @else
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                                لا يوجد إجراء متاح على هذا الطلب حاليًا.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="card p-12 text-center text-slate-500">لا توجد طلبات ضمن الفلاتر الحالية.</div>
        @endforelse
    </div>

    <div>{{ $leaveRequests->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
function partialApprovalPicker(dateOptions) {
    return {
        decision: 'approved',
        dates: Array.isArray(dateOptions) ? dateOptions : [],
        selectedDates: [],

        toggleDate(dateValue) {
            if (this.decision !== 'partially_approved') {
                return;
            }

            if (this.isSelected(dateValue)) {
                this.selectedDates = this.selectedDates.filter(function (item) {
                    return item !== dateValue;
                });
                return;
            }

            this.selectedDates.push(dateValue);
            this.selectedDates.sort();
        },

        isSelected(dateValue) {
            return this.selectedDates.includes(dateValue);
        },

        clearDates() {
            this.selectedDates = [];
        },

        init() {
            this.$watch('decision', (value) => {
                if (value !== 'partially_approved') {
                    this.clearDates();
                }
            });
        },
    };
}
</script>
@endpush
