@extends('layouts.app')

@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')
@section('page-subtitle')مرحباً، {{ auth()->user()->name }} — {{ $dashboardMonthLabel ?? now()->locale('ar')->isoFormat('MMMM YYYY') }}@endsection

@section('content')

@if(auth()->user()->isDepartmentManager() && isset($departmentManagerSummary) && $departmentManagerSummary)
<div class="mb-5 rounded-3xl border border-white/30 overflow-hidden relative">
    <div class="absolute inset-0" style="background: radial-gradient(circle at 80% 20%, rgba(231,197,57,.2), transparent 42%), radial-gradient(circle at 10% 80%, rgba(77,155,151,.24), transparent 45%), linear-gradient(135deg, #2f6f9a 0%, #2f7b76 100%);"></div>
    <div class="relative p-5 sm:p-6 text-white">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-white/70 mb-2">Department Manager Dashboard</p>
                <h2 class="text-2xl sm:text-3xl font-black leading-tight">لوحة مختصرة لفريقك</h2>
                <p class="text-sm text-white/80 mt-2">مؤشرات الحضور والمهام وتقييم الأداء اليومي لأعضاء قسمك فقط</p>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:gap-3 w-full lg:w-auto lg:min-w-[320px]">
                <div class="rounded-2xl bg-white/15 border border-white/20 p-3 text-center">
                    <p class="text-xs text-white/70">عدد أعضاء القسم</p>
                    <p class="text-xl font-black mt-1">{{ $departmentManagerSummary['team_size'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 border border-white/20 p-3 text-center">
                    <p class="text-xs text-white/70">متوسط التقييم اليومي</p>
                    <p class="text-xl font-black mt-1">{{ number_format((float) $departmentManagerSummary['avg_daily_rating'], 2) }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 border border-white/20 p-3 text-center">
                    <p class="text-xs text-white/70">مهام منجزة</p>
                    <p class="text-xl font-black mt-1">{{ $departmentManagerSummary['done_tasks'] }}/{{ $departmentManagerSummary['total_tasks'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 border border-white/20 p-3 text-center">
                    <p class="text-xs text-white/70">مهام قيد التنفيذ</p>
                    <p class="text-xl font-black mt-1">{{ $departmentManagerSummary['in_progress_tasks'] }}</p>
                </div>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3">
            <a href="{{ route('tasks.admin.index') }}" class="px-4 py-3 rounded-xl bg-white/15 border border-white/20 hover:bg-white/25 text-sm font-semibold text-center transition">مهام القسم</a>
            <a href="{{ route('daily-performance.review.index') }}" class="px-4 py-3 rounded-xl bg-white/15 border border-white/20 hover:bg-white/25 text-sm font-semibold text-center transition">تقييم الأداء اليومي</a>
            <a href="{{ route('employees.index') }}" class="px-4 py-3 rounded-xl bg-white/15 border border-white/20 hover:bg-white/25 text-sm font-semibold text-center transition">أعضاء القسم</a>
        </div>
    </div>
</div>
@endif

{{-- بطاقات الإحصاء الرئيسية --}}
<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-5 mb-5">

    {{-- إجمالي الموظفين --}}
    <div class="card p-4 sm:p-5 hover:shadow-card-hover transition-all duration-200 animate-slide-up">
        <div class="flex items-start justify-between mb-2 sm:mb-3">
            <div class="stat-card-icon !w-9 !h-9 sm:!w-11 sm:!h-11" style="background: rgba(69, 150, 207, 0.12);">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" style="color: #4596cf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <span class="badge-blue hidden sm:inline-flex">نشط</span>
        </div>
        <p class="text-2xl sm:text-3xl font-black text-slate-800">{{ $totalEmployees ?? 0 }}</p>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">إجمالي الموظفين</p>
    </div>

    {{-- نسبة الحضور --}}
    <div class="card p-4 sm:p-5 hover:shadow-card-hover transition-all duration-200 animate-slide-up" style="animation-delay: 0.05s;">
        <div class="flex items-start justify-between mb-2 sm:mb-3">
            <div class="stat-card-icon !w-9 !h-9 sm:!w-11 sm:!h-11" style="background: rgba(77, 155, 151, 0.12);">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" style="color: #4d9b97;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="badge-teal hidden sm:inline-flex">هذا الشهر</span>
        </div>
        @if($hasCurrentData ?? false)
            <p class="text-2xl sm:text-3xl font-black text-slate-800">{{ $attendanceRate }}<span class="text-sm sm:text-base font-semibold text-slate-500">%</span></p>
        @else
            <p class="text-2xl sm:text-3xl font-black text-slate-400">—</p>
        @endif
        <p class="text-xs sm:text-sm text-slate-500 mt-1">نسبة الحضور</p>
    </div>

    {{-- التأخير --}}
    <div class="card p-4 sm:p-5 hover:shadow-card-hover transition-all duration-200 animate-slide-up" style="animation-delay: 0.1s;">
        <div class="flex items-start justify-between mb-2 sm:mb-3">
            <div class="stat-card-icon !w-9 !h-9 sm:!w-11 sm:!h-11" style="background: rgba(231, 197, 57, 0.12);">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" style="color: #ca9a0a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="badge-warning hidden sm:inline-flex">تأخير</span>
        </div>
        <p class="text-2xl sm:text-3xl font-black text-slate-800">
            {{ $totalLateHours ?? 0 }}<span class="text-sm sm:text-base font-semibold text-slate-500 mr-0.5">س</span>
        </p>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">إجمالي التأخير</p>
    </div>

    {{-- Overtime --}}
    <div class="card p-4 sm:p-5 hover:shadow-card-hover transition-all duration-200 animate-slide-up" style="animation-delay: 0.15s;">
        <div class="flex items-start justify-between mb-2 sm:mb-3">
            <div class="stat-card-icon !w-9 !h-9 sm:!w-11 sm:!h-11" style="background: rgba(49, 113, 157, 0.12);">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" style="color: #31719d;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <span class="badge-blue hidden sm:inline-flex">أوفرتايم</span>
        </div>
        <p class="text-2xl sm:text-3xl font-black text-slate-800">
            {{ $totalOTHours ?? 0 }}<span class="text-sm sm:text-base font-semibold text-slate-500 mr-0.5">س</span>
        </p>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">إجمالي Overtime</p>
    </div>

</div>

{{-- تنبيه: لا توجد بيانات للشهر الحالي --}}
@if(!($hasCurrentData ?? false))
<div class="mb-5 p-4 bg-amber-50 border border-amber-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center gap-3">
    <div class="flex items-center gap-2 flex-1">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-amber-700 font-medium">
            لا توجد بيانات حضور لشهر <strong>{{ $dashboardMonthLabel ?? now()->locale('ar')->isoFormat('MMMM YYYY') }}</strong> بعد.
        </p>
    </div>
    @if(auth()->user()->isAdminLike())
    <a href="{{ route('import.form') }}"
       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-500 text-white text-xs font-bold
              hover:bg-amber-600 transition whitespace-nowrap w-full sm:w-auto justify-center">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
        </svg>
        رفع ملف الشهر
    </a>
    @endif
</div>
@endif

{{-- الصف الثاني: الإجراءات السريعة + آخر استيراد --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5 mb-5">

    {{-- إجراءات سريعة --}}
    @if(auth()->user()->isAdminLike())
    <div class="lg:col-span-2 card p-4 sm:p-6">
        <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" style="color: #4596cf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            إجراءات سريعة
        </h3>
        <div class="grid grid-cols-3 gap-2 sm:gap-3">

            <a href="{{ route('import.form') }}"
               class="flex flex-col items-center gap-1.5 sm:gap-2 p-3 sm:p-4 rounded-2xl text-white text-center text-xs font-semibold
                      transition-all hover:shadow-lg hover:scale-[1.02] active:scale-[0.98]"
               style="background: linear-gradient(135deg, #4596cf, #31719d);">
                <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <span class="leading-tight">رفع ملف</span>
            </a>

            <a href="{{ route('employees.create') }}"
               class="flex flex-col items-center gap-1.5 sm:gap-2 p-3 sm:p-4 rounded-2xl text-white text-center text-xs font-semibold
                      transition-all hover:shadow-lg hover:scale-[1.02] active:scale-[0.98]"
               style="background: linear-gradient(135deg, #4d9b97, #317c77);">
                <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                <span class="leading-tight">إضافة موظف</span>
            </a>

            <a href="{{ route('attendance.report') }}"
               class="flex flex-col items-center gap-1.5 sm:gap-2 p-3 sm:p-4 rounded-2xl text-slate-700 text-center text-xs font-semibold
                      bg-slate-100 hover:bg-slate-200 transition-all hover:shadow-md hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="leading-tight">تقرير الحضور</span>
            </a>

            <a href="{{ route('payroll.calculate.form') }}"
               class="flex flex-col items-center gap-1.5 sm:gap-2 p-3 sm:p-4 rounded-2xl text-slate-800 text-center text-xs font-semibold
                      transition-all hover:shadow-lg hover:scale-[1.02] active:scale-[0.98]"
               style="background: linear-gradient(135deg, #e7c539, #ca9a0a);">
                <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 20h16a2 2 0 002-2V8a2 2 0 00-2-2h-5L11 4H5a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span class="leading-tight">حساب الرواتب</span>
            </a>

            <a href="{{ route('employees.index') }}"
               class="flex flex-col items-center gap-1.5 sm:gap-2 p-3 sm:p-4 rounded-2xl text-slate-700 text-center text-xs font-semibold
                      bg-slate-100 hover:bg-slate-200 transition-all hover:shadow-md hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="leading-tight">الموظفين</span>
            </a>

            <a href="{{ route('settings.index') }}"
               class="flex flex-col items-center gap-1.5 sm:gap-2 p-3 sm:p-4 rounded-2xl text-slate-700 text-center text-xs font-semibold
                      bg-slate-100 hover:bg-slate-200 transition-all hover:shadow-md hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="leading-tight">الإعدادات</span>
            </a>

        </div>
    </div>
    @endif

    {{-- آخر استيراد --}}
    <div class="{{ auth()->user()->isAdminLike() ? '' : 'lg:col-span-3' }} card p-4 sm:p-6 flex flex-col">
        <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" style="color: #4d9b97;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            آخر عملية استيراد
        </h3>

        @if(isset($lastBatch))
        <div class="flex-1">
            <div class="flex items-start sm:items-center justify-between mb-3 gap-2">
                <div class="min-w-0">
                    <p class="font-bold text-slate-800 truncate">{{ $lastBatch->month_name }} {{ $lastBatch->year }}</p>
                    <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $lastBatch->file_name }}</p>
                </div>
                @php
                    $statusColors = [
                        'completed' => 'badge-success',
                        'failed'    => 'badge-danger',
                        'processing'=> 'badge-info',
                        'pending'   => 'badge-warning',
                    ];
                @endphp
                <span class="{{ $statusColors[$lastBatch->status->value] ?? 'badge-gray' }} flex-shrink-0">
                    {{ $lastBatch->status->label() }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-slate-50 rounded-xl p-3 text-center">
                    <p class="text-xl font-black" style="color: #4596cf;">{{ $lastBatch->employees_count }}</p>
                    <p class="text-xs text-slate-500">موظف</p>
                </div>
                <div class="bg-slate-50 rounded-xl p-3 text-center">
                    <p class="text-xl font-black" style="color: #4d9b97;">{{ $lastBatch->records_count }}</p>
                    <p class="text-xs text-slate-500">سجل</p>
                </div>
            </div>

            <p class="text-xs text-slate-400 text-center">{{ $lastBatch->created_at->diffForHumans() }}</p>
        </div>
        @else
        <div class="flex-1 flex flex-col items-center justify-center text-center py-6">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-500 mb-1">لا يوجد استيراد بعد</p>
            <p class="text-xs text-slate-400 mb-4">ابدأ برفع أول ملف Excel</p>
            @if(auth()->user()->isAdminLike())
            <a href="{{ route('import.form') }}" class="btn-primary btn-sm">
                رفع ملف الآن
            </a>
            @endif
        </div>
        @endif
    </div>

</div>

{{-- الصف الثالث: أكثر الموظفين تأخيراً + أعلى أوفرتايم + أعلى ساعات عمل --}}
@if(($hasCurrentData ?? false))
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5 mb-5">

    {{-- أكثر الموظفين تأخيراً --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: rgba(202, 154, 10, 0.2);">
                    <svg class="w-4 h-4" style="color: #e7c539;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white leading-tight">أكثر الموظفين تأخيراً</h3>
                    <p class="text-xs text-white/60">{{ now()->locale('ar')->isoFormat('MMMM YYYY') }}</p>
                </div>
            </div>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse(collect($topLateEmployees ?? [])->take(5) as $idx => $item)
            @php
                $lateAvatarUrl = $item['employee']?->user?->profile?->avatar_path
                    ? route('media.avatar', ['path' => $item['employee']->user->profile->avatar_path])
                    : null;
            @endphp
            <div class="flex items-center gap-3 px-4 py-3">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                    {{ $idx === 0 ? 'bg-red-100 text-red-600' : ($idx === 1 ? 'bg-orange-100 text-orange-600' : 'bg-slate-100 text-slate-500') }}">
                    {{ $idx + 1 }}
                </span>
                <div class="w-8 h-8 rounded-xl overflow-hidden flex items-center justify-center text-xs font-bold text-slate-700 flex-shrink-0"
                     style="background:#fff;border:1.5px solid #e2e8f0;">
                    @if($lateAvatarUrl)
                        <img src="{{ $lateAvatarUrl }}" alt="{{ $item['employee']->name }}" class="w-full h-full object-cover">
                    @else
                        {{ mb_substr($item['employee']->name, 0, 1) }}
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 truncate">{{ $item['employee']->name }}</p>
                    <p class="text-xs text-slate-500">{{ $item['employee']->position_line }}</p>
                </div>
                <div class="text-left flex-shrink-0">
                    <p class="text-sm font-bold" style="color: #ca9a0a;">{{ $item['late_hours'] }} س</p>
                    <p class="text-xs text-slate-400">{{ $item['late_minutes'] }} دقيقة</p>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center">
                <svg class="w-10 h-10 text-slate-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-slate-400">لا يوجد تأخير مسجل 🎉</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- أعلى أوفرتايم --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: rgba(69, 150, 207, 0.2);">
                    <svg class="w-4 h-4" style="color: #4596cf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white leading-tight">أعلى ساعات أوفرتايم</h3>
                    <p class="text-xs text-white/60">{{ now()->locale('ar')->isoFormat('MMMM YYYY') }}</p>
                </div>
            </div>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse(collect($topOTEmployees ?? [])->take(5) as $idx => $item)
            @php
                $otAvatarUrl = $item['employee']?->user?->profile?->avatar_path
                    ? route('media.avatar', ['path' => $item['employee']->user->profile->avatar_path])
                    : null;
            @endphp
            <div class="flex items-center gap-3 px-4 py-3">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                    {{ $idx === 0 ? 'bg-blue-100 text-blue-600' : ($idx === 1 ? 'bg-sky-100 text-sky-600' : 'bg-slate-100 text-slate-500') }}">
                    {{ $idx + 1 }}
                </span>
                <div class="w-8 h-8 rounded-xl overflow-hidden flex items-center justify-center text-xs font-bold text-slate-700 flex-shrink-0"
                     style="background:#fff;border:1.5px solid #e2e8f0;">
                    @if($otAvatarUrl)
                        <img src="{{ $otAvatarUrl }}" alt="{{ $item['employee']->name }}" class="w-full h-full object-cover">
                    @else
                        {{ mb_substr($item['employee']->name, 0, 1) }}
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 truncate">{{ $item['employee']->name }}</p>
                    <p class="text-xs text-slate-500">{{ $item['employee']->position_line }}</p>
                </div>
                <div class="text-left flex-shrink-0">
                    <p class="text-sm font-bold" style="color: #4596cf;">{{ $item['ot_hours'] }} س</p>
                    <p class="text-xs text-slate-400">{{ $item['ot_minutes'] }} دقيقة</p>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center">
                <svg class="w-10 h-10 text-slate-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <p class="text-sm text-slate-400">لا يوجد أوفرتايم مسجل</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- أكثر الموظفين في ساعات العمل --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: rgba(77, 155, 151, 0.2);">
                    <svg class="w-4 h-4" style="color: #4d9b97;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m3 6V7m3 10v-4m3 4V5M3 21h18"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white leading-tight">أكثر الموظفين في ساعات العمل</h3>
                    <p class="text-xs text-white/60">{{ now()->locale('ar')->isoFormat('MMMM YYYY') }}</p>
                </div>
            </div>
        </div>
        @php
            $maxWorkMinutes = max(1, (int) collect($topWorkEmployees ?? [])->max('work_minutes'));
        @endphp
        <div class="divide-y divide-slate-100">
            @forelse(collect($topWorkEmployees ?? [])->take(5) as $idx => $item)
            @php
                $workAvatarUrl = $item['employee']?->user?->profile?->avatar_path
                    ? route('media.avatar', ['path' => $item['employee']->user->profile->avatar_path])
                    : null;
                $workRatio = min(100, round(((int) $item['work_minutes'] / $maxWorkMinutes) * 100, 1));
            @endphp
            <div class="px-4 py-3">
                <div class="flex items-center gap-3 mb-2">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                        {{ $idx === 0 ? 'bg-emerald-100 text-emerald-700' : ($idx === 1 ? 'bg-teal-100 text-teal-700' : 'bg-slate-100 text-slate-500') }}">
                        {{ $idx + 1 }}
                    </span>
                    <div class="w-8 h-8 rounded-xl overflow-hidden flex items-center justify-center text-xs font-bold text-slate-700 flex-shrink-0"
                         style="background:#fff;border:1.5px solid #e2e8f0;">
                        @if($workAvatarUrl)
                            <img src="{{ $workAvatarUrl }}" alt="{{ $item['employee']->name }}" class="w-full h-full object-cover">
                        @else
                            {{ mb_substr($item['employee']->name, 0, 1) }}
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $item['employee']->name }}</p>
                        <p class="text-xs text-slate-500">{{ $item['employee']->position_line }}</p>
                    </div>
                    <p class="text-sm font-bold text-emerald-700 flex-shrink-0">{{ $item['work_hours'] }} س</p>
                </div>
                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full js-progress-fill" data-width="{{ $workRatio }}" style="background: linear-gradient(90deg, #4d9b97, #2a6a6a);"></div>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center">
                <svg class="w-10 h-10 text-slate-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m3 6V7m3 10v-4m3 4V5M3 21h18"/>
                </svg>
                <p class="text-sm text-slate-400">لا توجد ساعات عمل كافية للعرض</p>
            </div>
            @endforelse
        </div>
    </div>

</div>

{{-- الصف الرابع: Visualization لمؤشرات مهمة --}}
@php
    $presentCount = (int) ($presentDays ?? 0);
    $remoteCount = (int) ($remoteDays ?? 0);
    $onsiteCount = (int) ($onsiteDays ?? 0);
    $remotePercent = $presentCount > 0 ? round(($remoteCount / $presentCount) * 100, 1) : 0;
    $onsitePercent = $presentCount > 0 ? round(($onsiteCount / $presentCount) * 100, 1) : 0;
@endphp
<div class="grid grid-cols-1 gap-4 sm:gap-5 mb-5">
    <div class="card p-5 sm:p-6">
        <h3 class="text-sm font-bold text-slate-700 mb-4">توزيع نمط أيام العمل</h3>
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span>أيام Onsite</span>
                    <span>{{ $onsiteCount }} يوم ({{ $onsitePercent }}%)</span>
                </div>
                <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full js-progress-fill" data-width="{{ $onsitePercent }}" style="background: linear-gradient(90deg, #31719d, #4596cf);"></div>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span>أيام Remote</span>
                    <span>{{ $remoteCount }} يوم ({{ $remotePercent }}%)</span>
                </div>
                <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full js-progress-fill" data-width="{{ $remotePercent }}" style="background: linear-gradient(90deg, #4d9b97, #2a6a6a);"></div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 pt-1">
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3 text-center">
                    <p class="text-xs text-slate-500">إجمالي ساعات العمل</p>
                    <p class="text-xl font-black text-slate-800 mt-1">{{ $totalWorkHours ?? 0 }} <span class="text-xs font-semibold text-slate-500">س</span></p>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3 text-center">
                    <p class="text-xs text-slate-500">متوسط الساعات/اليوم</p>
                    <p class="text-xl font-black text-slate-800 mt-1">{{ $avgWorkHoursPerDay ?? 0 }} <span class="text-xs font-semibold text-slate-500">س</span></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- سجل الاستيرادات الأخيرة --}}
@if(isset($recentBatches) && $recentBatches->count() > 0)
<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <h3 class="text-sm font-bold text-white">سجل الاستيرادات الأخيرة</h3>
            <p class="text-xs text-white/60 mt-0.5">آخر 5 عمليات رفع</p>
        </div>
        <a href="{{ route('import.history') }}" class="text-xs font-semibold text-white/80 hover:text-white transition">
            عرض الكل ←
        </a>
    </div>

    {{-- جدول للشاشات المتوسطة وما فوق --}}
    <div class="hidden sm:block overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>الشهر / السنة</th>
                    <th>الموظفين</th>
                    <th>السجلات</th>
                    <th>الحالة</th>
                    <th>تاريخ الرفع</th>
                    <th class="text-center">التفاصيل</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentBatches as $batch)
                @php
                    $sc = ['completed' => 'badge-success', 'failed' => 'badge-danger', 'processing' => 'badge-info', 'pending' => 'badge-warning'];
                @endphp
                <tr>
                    <td class="font-semibold text-slate-800">{{ $batch->month_name }} {{ $batch->year }}</td>
                    <td>{{ $batch->employees_count }}</td>
                    <td>{{ $batch->records_count }}</td>
                    <td>
                        <span class="{{ $sc[$batch->status->value] ?? 'badge-gray' }}">{{ $batch->status->label() }}</span>
                    </td>
                    <td class="text-slate-500">{{ $batch->created_at->format('Y-m-d') }}</td>
                    <td class="text-center">
                        <a href="{{ route('attendance.report', ['month' => $batch->month, 'year' => $batch->year]) }}" class="btn-ghost btn-sm">
                            عرض
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- قائمة للموبايل --}}
    <div class="sm:hidden divide-y divide-slate-100">
        @foreach($recentBatches as $batch)
        @php
            $sc = ['completed' => 'badge-success', 'failed' => 'badge-danger', 'processing' => 'badge-info', 'pending' => 'badge-warning'];
        @endphp
        <div class="px-4 py-3 flex items-center justify-between gap-2">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-800">{{ $batch->month_name }} {{ $batch->year }}</p>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ $batch->employees_count }} موظف · {{ $batch->records_count }} سجل
                </p>
            </div>
            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                <span class="{{ $sc[$batch->status->value] ?? 'badge-gray' }}">{{ $batch->status->label() }}</span>
                <p class="text-xs text-slate-400">{{ $batch->created_at->format('Y-m-d') }}</p>
                <a href="{{ route('attendance.report', ['month' => $batch->month, 'year' => $batch->year]) }}" class="btn-ghost btn-sm mt-1">
                    عرض
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.js-progress-fill').forEach(function (el) {
        var width = Number(el.getAttribute('data-width') || 0);
        var safeWidth = Math.max(0, Math.min(100, width));
        el.style.width = safeWidth + '%';
    });
})();
</script>
@endpush
