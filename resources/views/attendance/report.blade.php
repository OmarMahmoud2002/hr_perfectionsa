@extends('layouts.app')

@php
    $monthName = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY');
@endphp

@section('title', 'تقرير الحضور - ' . $monthName)
@section('page-title', 'تقرير الحضور')
@section('page-subtitle', $monthName)

@section('content')

{{-- Breadcrumb --}}
<nav class="breadcrumb">
    <a href="{{ route('attendance.index') }}">تقارير الحضور</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    <span class="text-slate-700 font-medium">{{ $monthName }}</span>
</nav>

{{-- فلترة الشهر --}}
<div class="card mb-5">
    <div class="card-body">
        <form action="{{ route('attendance.report') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="form-group mb-0">
                <label class="form-label">الشهر</label>
                <select name="month" class="form-input sm:w-40">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->locale('ar')->isoFormat('MMMM') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">السنة</label>
                <select name="year" class="form-input sm:w-32">
                    @foreach(range(now()->year, now()->year - 3) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mb-0">
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                    </svg>
                    عرض التقرير
                </button>
            </div>

            {{-- روابط سريعة للشهور المتاحة --}}
            @if($availableMonths->isNotEmpty())
            <div class="mr-auto flex flex-wrap gap-2 items-center">
                <span class="text-xs text-slate-400">شهور مستوردة:</span>
                @foreach($availableMonths->take(6) as $b)
                <a href="{{ route('attendance.report', ['month' => $b->month, 'year' => $b->year]) }}"
                   class="text-xs px-2.5 py-1 rounded-lg transition-all
                          {{ $month == $b->month && $year == $b->year
                              ? 'bg-secondary-100 text-secondary-700 font-semibold'
                              : 'bg-slate-100 text-slate-600 hover:bg-secondary-50 hover:text-secondary-600' }}">
                    {{ \Carbon\Carbon::create($b->year, $b->month, 1)->locale('ar')->isoFormat('MMM YY') }}
                </a>
                @endforeach
            </div>
            @endif
        </form>
    </div>
</div>

@if(!$batch)
{{-- لا توجد بيانات لهذا الشهر --}}
<div class="card p-10 text-center">
    <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-amber-50">
        <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
    </div>
    <h3 class="text-base font-bold text-slate-700 mb-1">لا توجد بيانات لـ {{ $monthName }}</h3>
    <p class="text-sm text-slate-500">لم يتم رفع ملف الحضور لهذا الشهر بعد.</p>
    @if(auth()->user()->isAdmin())
    <div class="mt-4">
        <a href="{{ route('import.form') }}" class="btn-primary btn-sm">رفع ملف الشهر</a>
    </div>
    @endif
</div>

@elseif($employeeStats->isEmpty())
<div class="card p-10 text-center">
    <p class="text-slate-500">لا توجد سجلات حضور لهذا الشهر.</p>
</div>

@else

{{-- بطاقات ملخص الشهر --}}
@php
    $totalPresent  = $employeeStats->sum('total_present_days');
    $totalAbsent   = $employeeStats->sum('total_absent_days');
    $totalLate     = $employeeStats->sum('total_late_minutes');
    $totalOT       = $employeeStats->sum('total_overtime_minutes');
    $empCount      = $employeeStats->count();
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-4">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-emerald-100">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs text-slate-500">إجمالي أيام الحضور</span>
        </div>
        <p class="text-2xl font-black text-emerald-600">{{ $totalPresent }}</p>
    </div>
    <div class="card p-4">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-red-100">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs text-slate-500">إجمالي أيام الغياب</span>
        </div>
        <p class="text-2xl font-black text-red-500">{{ $totalAbsent }}</p>
    </div>
    <div class="card p-4">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-amber-100">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs text-slate-500">إجمالي التأخير</span>
        </div>
        <p class="text-2xl font-black text-amber-600">
            {{ floor($totalLate / 60) }}<span class="text-base font-normal">س</span>
            {{ $totalLate % 60 }}<span class="text-base font-normal">د</span>
        </p>
    </div>
    <div class="card p-4">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: rgba(49,113,157,0.1);">
                <svg class="w-5 h-5" style="color: #31719d;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <span class="text-xs text-slate-500">إجمالي Overtime</span>
        </div>
        <p class="text-2xl font-black" style="color: #31719d;">
            {{ floor($totalOT / 60) }}<span class="text-base font-normal">س</span>
            {{ $totalOT % 60 }}<span class="text-base font-normal">د</span>
        </p>
    </div>
</div>

{{-- الإجازات الرسمية في الشهر --}}
@if(!empty($publicHolidays))
<div class="alert-info mb-5">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        <p class="font-semibold mb-1">إجازات رسمية مسجلة في هذا الشهر:</p>
        <div class="flex flex-wrap gap-2 mt-1">
            @foreach($batch->publicHolidays as $holiday)
            <span class="text-xs bg-sky-100 text-sky-700 px-2.5 py-1 rounded-lg font-medium">
                {{ $holiday->name }} — {{ \Carbon\Carbon::parse($holiday->date)->locale('ar')->isoFormat('D MMMM') }}
            </span>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- جدول تقرير الحضور --}}
<div class="card overflow-hidden">
    <div class="card-header">
        <h3 class="text-sm font-bold text-slate-700">تفاصيل الحضور — {{ $monthName }}</h3>
        <span class="badge-blue">{{ $empCount }} موظف</span>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th class="text-center">أيام العمل</th>
                    <th class="text-center">أيام الحضور</th>
                    <th class="text-center">أيام الغياب</th>
                    <th class="text-center">التأخير</th>
                    <th class="text-center">Overtime</th>
                    <th class="text-center">نسبة الحضور</th>
                    <th class="text-center">التفاصيل</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employeeStats as $stat)
                @php
                    $emp         = $stat['employee'];
                    $workDays    = $stat['total_working_days'];
                    $presentDays = $stat['total_present_days'];
                    $absentDays  = $stat['total_absent_days'];
                    $lateMin     = $stat['total_late_minutes'];
                    $otMin       = $stat['total_overtime_minutes'];
                    $weeklyLeaveDays = $stat['total_weekly_leave_days'] ?? 0;
                    $effectiveWorkDays = $workDays - $weeklyLeaveDays;
                    $rate        = $effectiveWorkDays > 0 ? round(($presentDays / $effectiveWorkDays) * 100) : 0;
                @endphp
                <tr>
                    {{-- الموظف --}}
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                 style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                                {{ mb_substr($emp->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">{{ $emp->name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ $emp->ac_no }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- أيام العمل --}}
                    <td class="text-center">
                        <span class="font-semibold text-slate-700">{{ $workDays }}</span>
                    </td>

                    {{-- أيام الحضور --}}
                    <td class="text-center">
                        <span class="font-bold text-emerald-600">{{ $presentDays }}</span>
                    </td>

                    {{-- أيام الغياب --}}
                    <td class="text-center">
                        @if($absentDays > 0)
                            <span class="font-bold text-red-500">{{ $absentDays }}</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>

                    {{-- التأخير --}}
                    <td class="text-center">
                        @if($lateMin > 0)
                            <span class="font-semibold text-amber-600 text-xs">
                                {{ floor($lateMin / 60) }}:{{ str_pad($lateMin % 60, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>

                    {{-- Overtime --}}
                    <td class="text-center">
                        @if($otMin > 0)
                            <span class="font-semibold text-xs" style="color: #31719d;">
                                {{ floor($otMin / 60) }}:{{ str_pad($otMin % 60, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>

                    {{-- نسبة الحضور --}}
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-16 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                     style="width: {{ $rate }}%;
                                            background: {{ $rate >= 90 ? '#10b981' : ($rate >= 70 ? '#f59e0b' : '#ef4444') }};">
                                </div>
                            </div>
                            <span class="text-xs font-semibold
                                         {{ $rate >= 90 ? 'text-emerald-600' : ($rate >= 70 ? 'text-amber-600' : 'text-red-500') }}">
                                {{ $rate }}%
                            </span>
                        </div>
                    </td>

                    {{-- التفاصيل --}}
                    <td class="text-center">
                        <a href="{{ route('attendance.employee', [$emp->id, 'month' => $month, 'year' => $year]) }}"
                           class="btn-ghost btn-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            عرض
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endif

@endsection
