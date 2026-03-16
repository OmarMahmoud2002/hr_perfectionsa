@extends('layouts.app')

@php
    $monthName = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY');
@endphp

@section('title', 'حضور ' . $employee->name . ' — ' . $monthName)
@section('page-title', $employee->name)
@section('page-subtitle', 'تقرير الحضور — ' . $monthName)

@section('content')

{{-- Breadcrumb --}}
<nav class="breadcrumb">
    <a href="{{ route('attendance.index') }}">تقارير الحضور</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    <a href="{{ route('attendance.report', ['month' => $month, 'year' => $year]) }}">{{ $monthName }}</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    <span class="text-slate-700 font-medium">{{ $employee->name }}</span>
</nav>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- الجانب الأيمن: بطاقة الموظف + ملخص + فلتر --}}
    <div class="space-y-5">

        {{-- بيانات الموظف --}}
        <div class="card p-5">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white text-2xl font-black flex-shrink-0"
                     style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                    {{ mb_substr($employee->name, 0, 1) }}
                </div>
                <div>
                    <h2 class="font-bold text-base text-slate-800">{{ $employee->name }}</h2>
                    <span class="font-mono text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{{ $employee->ac_no }}</span>
                </div>
            </div>

            {{-- فلتر الشهر --}}
            <form action="{{ route('attendance.employee', $employee->id) }}" method="GET" class="space-y-2">
                <div class="flex gap-2">
                    <select name="month" class="form-input flex-1 text-sm py-1.5">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->locale('ar')->isoFormat('MMMM') }}
                            </option>
                        @endforeach
                    </select>
                    <select name="year" class="form-input w-24 text-sm py-1.5">
                        @foreach(range(now()->year, now()->year - 2) as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-primary w-full justify-center btn-sm">
                    عرض
                </button>
            </form>
        </div>

        {{-- ملخص الشهر --}}
        <div class="card p-5">
            <h3 class="text-sm font-bold text-slate-700 mb-4">ملخص {{ $monthName }}</h3>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-emerald-50 rounded-xl p-3 text-center">
                    <p class="text-2xl font-black text-emerald-600">{{ $stats['total_present_days'] }}</p>
                    <p class="text-xs text-emerald-700 mt-0.5">يوم حضور</p>
                </div>
                <div class="bg-red-50 rounded-xl p-3 text-center">
                    <p class="text-2xl font-black text-red-500">{{ $stats['total_absent_days'] }}</p>
                    <p class="text-xs text-red-600 mt-0.5">يوم غياب</p>
                </div>
            </div>

            @if(($stats['total_weekly_leave_days'] ?? 0) > 0)
            <div class="flex items-center justify-between p-3 rounded-xl mb-3" style="background:#ede9fe;">
                <span class="text-xs font-medium" style="color:#7c3aed;">إجازات أسبوعية</span>
                <span class="font-bold" style="color:#7c3aed;">{{ $stats['total_weekly_leave_days'] }} يوم</span>
            </div>
            @endif

            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-amber-50 rounded-xl">
                    <span class="text-xs text-amber-700 font-medium">إجمالي التأخير</span>
                    <span class="font-bold text-amber-600">
                        {{ floor($stats['total_late_minutes'] / 60) }}:{{ str_pad($stats['total_late_minutes'] % 60, 2, '0', STR_PAD_LEFT) }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" style="background: rgba(49,113,157,0.08);">
                    <span class="text-xs font-medium" style="color: #31719d;">إجمالي Overtime</span>
                    <span class="font-bold" style="color: #31719d;">
                        {{ floor($stats['total_overtime_minutes'] / 60) }}:{{ str_pad($stats['total_overtime_minutes'] % 60, 2, '0', STR_PAD_LEFT) }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                    <span class="text-xs text-slate-500">أيام العمل المفترضة</span>
                    <span class="font-semibold text-slate-700">{{ $stats['total_working_days'] }}</span>
                </div>
            </div>
        </div>

        {{-- أزرار الإجراءات --}}
        <div class="card p-4 space-y-2">
            {{-- تصدير Excel --}}
            <a href="{{ route('attendance.employee.export', $employee->id) }}?month={{ $month }}&year={{ $year }}"
               class="btn-ghost w-full justify-center text-sm flex items-center gap-2" style="
    background-color: #34d634b5;
">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                تصدير Excel
            </a>

            {{-- حساب المرتب (للمديرين فقط) --}}
            @if(auth()->user()->role === 'admin')
            <a href="{{ route('payroll.calculate.form') }}?month={{ $month }}&year={{ $year }}&employee_id={{ $employee->id }}"
               class="btn-primary w-full justify-center text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                حساب المرتب
            </a>
            @endif
        </div>

        {{-- دليل الألوان --}}
        <div class="card p-4">            <h4 class="text-xs font-bold text-slate-500 mb-3 uppercase tracking-wide">دليل الألوان</h4>
            <div class="space-y-2 text-xs">
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-emerald-100 border border-emerald-300 flex-shrink-0"></span><span class="text-slate-600">حضور عادي</span></div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-amber-100 border border-amber-300 flex-shrink-0"></span><span class="text-slate-600">حضور مع تأخير</span></div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-red-100 border border-red-300 flex-shrink-0"></span><span class="text-slate-600">غياب</span></div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-sky-100 border border-sky-300 flex-shrink-0"></span><span class="text-slate-600">إجازة رسمية</span></div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-slate-100 border border-slate-300 flex-shrink-0"></span><span class="text-slate-600">جمعة</span></div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-purple-100 border border-purple-300 flex-shrink-0"></span><span class="text-slate-600">إجازة أسبوعية</span></div>
            </div>
        </div>
    </div>

    {{-- الجانب الأيسر: التقويم اليومي --}}
    <div class="lg:col-span-2">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h3 class="text-sm font-bold text-slate-700">سجل الحضور اليومي</h3>
                <span class="badge-gray">{{ $dailyBreakdown->count() }} يوم</span>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>اليوم</th>
                            <th class="text-center">الحضور</th>
                            <th class="text-center">الانصراف</th>
                            <th class="text-center">التأخير</th>
                            <th class="text-center">Overtime</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dailyBreakdown as $day)
                        @php
                            $rowClass = match($day['status']) {
                                'present'        => 'bg-emerald-50/40',
                                'late'           => 'bg-amber-50/40',
                                'absent'         => 'bg-red-50/40',
                                'public_holiday' => 'bg-sky-50/40',
                                'friday'         => 'bg-slate-50/60 opacity-70',
                                'weekly_leave'   => 'bg-purple-50/60 opacity-80',
                                default          => '',
                            };
                            $record = $day['record'];
                        @endphp
                        <tr class="{{ $rowClass }}">
                            {{-- التاريخ --}}
                            <td class="font-mono text-xs text-slate-600">{{ $day['date']->format('Y-m-d') }}</td>

                            {{-- اليوم --}}
                            <td class="text-sm text-slate-600">{{ $day['day_name'] }}</td>

                            {{-- Clock In --}}
                            <td class="text-center">
                                @if($record && $record->clock_in)
                                    <span class="font-mono text-xs font-semibold text-slate-700">
                                        {{ substr($record->clock_in, 0, 5) }}
                                    </span>
                                @elseif(in_array($day['status'], ['friday', 'public_holiday', 'weekly_leave']))
                                    <span class="text-slate-300">—</span>
                                @else
                                    <span class="text-red-400 text-xs">غائب</span>
                                @endif
                            </td>

                            {{-- Clock Out --}}
                            <td class="text-center">
                                @if($record && $record->clock_out)
                                    <span class="font-mono text-xs font-semibold text-slate-700">
                                        {{ substr($record->clock_out, 0, 5) }}
                                    </span>
                                @elseif(in_array($day['status'], ['friday', 'public_holiday', 'weekly_leave']))
                                    <span class="text-slate-300">—</span>
                                @else
                                    <span class="text-red-400 text-xs">—</span>
                                @endif
                            </td>

                            {{-- التأخير --}}
                            <td class="text-center">
                                @if($record && $record->late_minutes > 0)
                                    <span class="text-xs font-semibold text-amber-600">
                                        {{ floor($record->late_minutes / 60) > 0 ? floor($record->late_minutes / 60) . 'س ' : '' }}{{ $record->late_minutes % 60 }}د
                                    </span>
                                @else
                                    <span class="text-slate-300 text-xs">—</span>
                                @endif
                            </td>

                            {{-- Overtime --}}
                            <td class="text-center">
                                @if($record && $record->overtime_minutes > 0)
                                    <span class="text-xs font-semibold" style="color: #31719d;">
                                        {{ floor($record->overtime_minutes / 60) > 0 ? floor($record->overtime_minutes / 60) . 'س ' : '' }}{{ $record->overtime_minutes % 60 }}د
                                    </span>
                                @else
                                    <span class="text-slate-300 text-xs">—</span>
                                @endif
                            </td>

                            {{-- الحالة --}}
                            <td>
                                @switch($day['status'])
                                    @case('present')
                                        <span class="badge-success">حاضر</span>
                                        @break
                                    @case('late')
                                        <span class="badge-warning">متأخر</span>
                                        @break
                                    @case('absent')
                                        <span class="badge-danger">غائب</span>
                                        @break
                                    @case('public_holiday')
                                        <span class="badge-info">إجازة رسمية</span>
                                        @break
                                    @case('friday')
                                        <span class="badge-gray">جمعة</span>
                                        @break
                                    @case('weekly_leave')
                                        <span class="badge" style="background:#ede9fe;color:#7c3aed;border:1px solid #ddd6fe;">إجازة أسبوعية</span>
                                        @break
                                @endswitch

                                @if($record && $record->notes)
                                    <span class="mr-1 text-xs text-slate-400" title="{{ $record->notes }}">
                                        <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
