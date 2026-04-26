@extends('layouts.app')

@section('title', 'إدارة طلبات الإجازة')
@section('page-title', 'إدارة طلبات الإجازة')
@section('page-subtitle', 'متابعة قرارات المراجعة حسب الدور مع حالة كل طلب')

@section('content')
@php
    $statusBadge = [
        'pending' => 'badge-warning',
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        'not_required' => 'badge-gray',
    ];

    $statusLabel = [
        'pending' => 'قيد المراجعة',
        'approved' => 'معتمد',
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
                    @if($isHrLike || $isManager)
                        <a href="{{ route('leave.approvals.employee-settings') }}" class="inline-flex items-center gap-2 mt-3 text-xs font-bold px-3 py-2 rounded-xl border border-white/35 bg-white/15 hover:bg-white/30 hover:-translate-y-0.5 active:translate-y-0 transition duration-200">
                            إعدادات الموظفين
                        </a>
                    @endif
                </div>
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full xl:w-auto xl:min-w-[560px]">
                    <select name="status" class="form-input !bg-white/95 !border-white/25 !h-9 !min-h-0 !py-1 !text-sm !w-full" onchange="this.form.submit()" style="height: 2.75rem !important; padding-right: 35px;">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>كل الحالات</option>
                        <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>قيد المراجعة</option>
                        <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>معتمد</option>
                        <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                    </select>
                    <select name="month" class="form-input !bg-white/95 !border-white/25 !h-9 !min-h-0 !py-1 !text-sm !w-full" onchange="this.form.submit()" style="height: 2.75rem !important; padding-right: 35px;">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ (int) $monthFilter === (int) $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create((int) $yearFilter, $m, 1)->locale('ar')->isoFormat('MMMM') }}
                            </option>
                        @endforeach
                    </select>
                    <input type="number" min="2020" max="2100" name="year" value="{{ $yearFilter }}" class="form-input !bg-white/95 !border-white/25 !h-9 !min-h-0 !py-1 !text-sm !w-full" onchange="this.form.submit()">
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
        <div class="card p-3 animate-slide-up" style="animation-delay:50ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">إجمالي الطلبات المعروضة</p>
            <p class="text-lg font-black text-slate-800 mt-1">{{ $leaveRequests->total() }}</p>
        </div>
        <div class="card p-3 animate-slide-up" style="animation-delay:90ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">طلبات قيد المراجعة</p>
            <p class="text-lg font-black text-amber-600 mt-1">{{ $leaveRequests->getCollection()->where('status', 'pending')->count() }}</p>
        </div>
        <div class="card p-3 animate-slide-up" style="animation-delay:130ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">صلاحية اتخاذ القرار</p>
            <p class="text-sm font-bold mt-2 text-slate-700">{{ $isHrLike ? 'HR' : ($isManager ? 'مدير عام' : ($isDepartmentManager ? 'مدير قسم' : 'عرض فقط')) }}</p>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($leaveRequests as $leave)
            @php
                $canDecide = auth()->user()->can('approve', $leave);
            @endphp

            <div class="card card-interactive overflow-hidden animate-slide-up" style="animation-delay:120ms; animation-fill-mode:both;" x-data="{ decision: 'approved', open: false }">
                <div class="p-5 border-b border-slate-100 bg-slate-50/70">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-bold text-slate-800 truncate">{{ $leave->employee?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-500">{{ $leave->employee?->position_line ?? '—' }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="{{ $statusBadge[$leave->status] ?? 'badge-gray' }}">{{ $statusLabel[$leave->status] ?? $leave->status }}</span>
                            <span class="badge-gray">{{ (int) $leave->requested_days }} يوم</span>
                            <span class="badge-gray">HR: {{ $statusLabel[$leave->hr_status] ?? $leave->hr_status }}</span>
                            @if($leave->manager_status !== 'not_required')
                                <span class="badge-gray">المدير: {{ $statusLabel[$leave->manager_status] ?? $leave->manager_status }}</span>
                            @endif
                            <button type="button" @click="open = !open" class="btn-ghost btn-sm">
                                <span x-show="!open">عرض التفاصيل</span>
                                <span x-show="open">إخفاء التفاصيل</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="open" x-transition class="p-5 grid grid-cols-1 xl:grid-cols-3 gap-5">
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
                                    <select name="decision" class="form-input" x-model="decision" style="padding-right: 35px;">
                                        <option value="approved">اعتماد</option>
                                        <option value="rejected">رفض</option>
                                    </select>
                                </div>

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
            <div class="empty-state animate-fade-in">
                <div class="empty-state-icon animate-float-soft">
                    <svg class="w-10 h-10" style="color: #31719d;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12h6m-3-3v6m9 1a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h3l2-2h4l2 2h3a2 2 0 012 2v8z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-extrabold text-slate-700 mb-2">لا توجد طلبات ضمن الفلاتر الحالية</h3>
                <p class="text-sm text-slate-500">جرّب تغيير الحالة أو الشهر أو السنة لعرض نتائج أخرى.</p>
            </div>
        @endforelse
    </div>

    <div>{{ $leaveRequests->links() }}</div>
</div>
@endsection
