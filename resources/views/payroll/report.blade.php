@extends('layouts.app')

@php
    $monthName      = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY');
    $singleEmployee = $singleEmployee ?? null;
@endphp

@section('title', 'كشف مرتبات — ' . $monthName)
@section('page-title', 'كشف مرتبات')
@section('page-subtitle', $monthName)

@section('content')

{{-- Breadcrumb --}}
<nav class="breadcrumb">
    <a href="{{ route('payroll.index') }}">كشوف المرتبات</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    <span class="text-slate-700 font-medium">{{ $monthName }}</span>
</nav>

{{-- Flash Messages --}}
@if(session('success'))
<div class="alert-success mb-5">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert-error mb-5">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('error') }}
</div>
@endif

{{-- التنقل بين الأشهر --}}
@if($availableMonths->isNotEmpty())
<div class="flex flex-wrap gap-2 mb-5 items-center">
    <span class="text-xs text-slate-500">تنقل بين الأشهر:</span>
    @foreach($availableMonths as $am)
    <a href="{{ route('payroll.report', [$am->month, $am->year]) }}"
       class="text-xs px-2.5 py-1 rounded-lg transition-all
              {{ $month == $am->month && $year == $am->year
                  ? 'bg-secondary-100 text-secondary-700 font-semibold'
                  : 'bg-slate-100 text-slate-600 hover:bg-secondary-50 hover:text-secondary-600' }}">
        {{ \Carbon\Carbon::create($am->year, $am->month, 1)->locale('ar')->isoFormat('MMM YY') }}
    </a>
    @endforeach
</div>
@endif

{{-- شريط الموظف المفرد (عند التصفية بموظف واحد) --}}
@if($singleEmployee)
<div class="flex items-center gap-4 p-4 bg-secondary-50 border border-secondary-200 rounded-2xl mb-5">
    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
         style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
        {{ mb_substr($singleEmployee->name, 0, 1) }}
    </div>
    <div class="flex-1 min-w-0">
        <p class="font-bold text-secondary-800 text-sm">عرض راتب: {{ $singleEmployee->name }}</p>
        <p class="text-xs text-secondary-600">{{ \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY') }}</p>
    </div>
    <a href="{{ route('payroll.report', [$month, $year]) }}"
       class="flex items-center gap-1.5 text-xs font-semibold text-secondary-700 hover:text-secondary-900 bg-white border border-secondary-200 hover:border-secondary-400 px-3 py-1.5 rounded-lg transition-all">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        عرض جميع الموظفين
    </a>
</div>
@endif

{{-- بطاقات الملخص --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-4">
        <p class="text-xs text-slate-500 mb-1">عدد الموظفين</p>
        <p class="text-2xl font-black text-slate-800">{{ $summary['total_employees'] }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-slate-500 mb-1">إجمالي الخصومات</p>
        <p class="text-2xl font-black text-red-500">
            {{ number_format($summary['total_late_deduction'] + $summary['total_absent_deduction'], 0) }}
            <span class="text-sm font-normal">ج.م</span>
        </p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-slate-500 mb-1">إجمالي المكافآت</p>
        <p class="text-2xl font-black text-emerald-600">
            {{ number_format($summary['total_overtime_bonus'] + $summary['total_attendance_bonus'], 0) }}
            <span class="text-sm font-normal">ج.م</span>
        </p>
        <p class="text-xs text-slate-400 mt-1">
            OT: {{ number_format($summary['total_overtime_bonus'], 0) }}
            @if($summary['total_attendance_bonus'] > 0)
             • حضور: {{ number_format($summary['total_attendance_bonus'], 0) }}
            @endif
        </p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-slate-500 mb-1">إجمالي صافي المرتبات</p>
        <p class="text-2xl font-black" style="color: #317c77;">
            {{ number_format($summary['total_net_salary'], 0) }}
            <span class="text-sm font-normal">ج.م</span>
        </p>
    </div>
</div>

{{-- شريط الإجراءات --}}
<div class="flex flex-wrap items-center gap-3 mb-4">
    @if(auth()->user()->isAdmin())
    <a href="{{ route('payroll.calculate.form', ['month' => $month, 'year' => $year]) }}"
       class="btn-gold btn-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        @if($singleEmployee) إعادة حساب {{ $singleEmployee->name }} @else إعادة الحساب @endif
    </a>
    @endif

    @if(!$singleEmployee)
    <a href="{{ route('payroll.export', [$month, $year]) }}"
       class="btn-teal btn-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        تصدير Excel
    </a>
    @endif

    @if($summary['locked_count'] > 0)
    <span class="badge-success mr-auto">
        <svg class="w-3.5 h-3.5 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        {{ $summary['locked_count'] }} مؤمّن
    </span>
    @endif
</div>

{{-- جدول كشف المرتبات --}}
@if($reports->isEmpty())
<div class="card p-10 text-center">
    <p class="text-slate-500">لا توجد بيانات مرتبات لهذا الشهر.</p>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('payroll.calculate.form', ['month' => $month, 'year' => $year]) }}"
       class="btn-primary btn-sm mt-3 inline-flex">احسب الآن</a>
    @endif
</div>

@else

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th class="text-center">أيام الحضور</th>
                    <th class="text-center">أيام الغياب</th>
                    <th class="text-center">التأخير</th>
                    <th class="text-center">OT</th>
                    <th class="text-center">الفرق</th>
                    <th class="text-left">المرتب الأساسي</th>
                    <th class="text-left">الخصومات</th>
                    <th class="text-left">المكافآت</th>
                    <th class="text-left font-bold">صافي المرتب</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                <tr class="{{ $report->is_locked ? 'bg-emerald-50/30' : '' }}">
                    {{-- الموظف --}}
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                 style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                                {{ mb_substr($report->employee?->name ?? '?', 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">{{ $report->employee?->name ?? '(موظف محذوف)' }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ $report->employee?->ac_no ?? '—' }}</p>
                            </div>
                            @if($report->is_locked)
                            <svg class="w-3.5 h-3.5 text-emerald-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            @endif
                        </div>
                    </td>

                    {{-- أيام الحضور --}}
                    <td class="text-center">
                        <span class="font-semibold text-emerald-600">{{ $report->total_present_days }}</span>
                        <span class="text-xs text-slate-400">/{{ $report->total_working_days }}</span>
                    </td>

                    {{-- أيام الغياب --}}
                    <td class="text-center">
                        @if($report->total_absent_days > 0)
                            <span class="font-semibold text-red-500">{{ $report->total_absent_days }}</span>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>

                    {{-- التأخير --}}
                    <td class="text-center">
                        @if($report->total_late_minutes > 0)
                            <span class="text-xs text-amber-600 font-semibold">
                                {{ floor($report->total_late_minutes / 60) }}:{{ str_pad($report->total_late_minutes % 60, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>

                    {{-- OT --}}
                    <td class="text-center">
                        @if($report->total_overtime_minutes > 0)
                            <span class="text-xs font-semibold" style="color: #31719d;">
                                {{ floor($report->total_overtime_minutes / 60) }}:{{ str_pad($report->total_overtime_minutes % 60, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>

                    {{-- الفرق (OT - التأخير) --}}
                    <td class="text-center">
                        @php $diff = $report->total_overtime_minutes - $report->total_late_minutes; @endphp
                        @if($diff > 0)
                            <span class="text-xs font-semibold text-emerald-600">
                                +{{ floor($diff / 60) }}:{{ str_pad($diff % 60, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @elseif($diff < 0)
                            <span class="text-xs font-semibold text-red-500">
                                -{{ floor(abs($diff) / 60) }}:{{ str_pad(abs($diff) % 60, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>

                    {{-- المرتب الأساسي --}}
                    <td class="text-left font-mono text-sm text-slate-700">
                        {{ number_format($report->basic_salary, 0) }}
                    </td>

                    {{-- الخصومات --}}
                    <td class="text-left">
                        @php $totalDeduct = $report->late_deduction + $report->absent_deduction; @endphp
                        @if($totalDeduct > 0)
                        <div class="text-red-600 font-mono text-sm">
                            <span class="font-semibold">{{ number_format($totalDeduct, 0) }}</span>
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">
                            @if($report->late_deduction > 0) تأخير: {{ number_format($report->late_deduction, 0) }} @endif
                            @if($report->late_deduction > 0 && $report->absent_deduction > 0) • @endif
                            @if($report->absent_deduction > 0) غياب: {{ number_format($report->absent_deduction, 0) }} @endif
                        </div>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>

                    {{-- المكافآت --}}
                    <td class="text-left">
                        @php $totalBonus = $report->overtime_bonus + $report->attendance_bonus; @endphp
                        @if($totalBonus > 0)
                        <div class="font-mono text-sm font-semibold text-emerald-600">
                            {{ number_format($totalBonus, 0) }}
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">
                            @if($report->overtime_bonus > 0) OT: {{ number_format($report->overtime_bonus, 0) }} @endif
                            @if($report->overtime_bonus > 0 && $report->attendance_bonus > 0) • @endif
                            @if($report->attendance_bonus > 0)
                                <span class="text-amber-600">حضور: {{ number_format($report->attendance_bonus, 0) }}</span>
                                <span class="text-slate-300">({{ $report->full_attendance_weeks }}أ)</span>
                            @endif
                        </div>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>

                    {{-- صافي المرتب --}}
                    <td class="text-left">
                        <span class="font-black text-base" style="color: #317c77;">
                            {{ number_format($report->net_salary, 0) }}
                        </span>
                        <span class="text-xs text-slate-400 mr-0.5">ج.م</span>
                    </td>
                </tr>
                @endforeach
            </tbody>

            {{-- Footer مجموع --}}
            <tfoot>
                <tr class="border-t-2 border-slate-200">
                    <td colspan="6" class="px-4 py-3 text-right font-bold text-slate-700 text-sm">
                        الإجمالي ({{ $summary['total_employees'] }} موظف)
                    </td>
                    <td class="px-4 py-3 font-bold font-mono text-slate-700 text-sm">
                        {{ number_format($summary['total_basic_salary'], 0) }}
                    </td>
                    <td class="px-4 py-3 font-bold font-mono text-red-600 text-sm">
                        {{ number_format($summary['total_late_deduction'] + $summary['total_absent_deduction'], 0) }}
                    </td>
                    <td class="px-4 py-3 font-bold font-mono text-emerald-600 text-sm">
                        {{ number_format($summary['total_overtime_bonus'] + $summary['total_attendance_bonus'], 0) }}
                    </td>
                    <td class="px-4 py-3 font-black text-base" style="color: #317c77;">
                        {{ number_format($summary['total_net_salary'], 0) }}
                        <span class="text-xs font-normal text-slate-400 mr-0.5">ج.م</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endif

@endsection
