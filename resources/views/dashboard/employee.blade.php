@extends('layouts.app')

@section('title', 'لوحتي')
@section('page-title', 'لوحتي')
@section('page-subtitle', 'ملخص حضورك الشخصي')

@section('content')
@php
    $periodLabel = \Carbon\Carbon::parse($periodStart)->locale('ar')->isoFormat('D MMM')
                 . ' — '
                 . \Carbon\Carbon::parse($periodEnd)->locale('ar')->isoFormat('D MMM YYYY');
@endphp

<div class="space-y-5">
    <div class="card p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-700">إحصائياتي للشهر</h3>
                <p class="text-xs text-slate-400 mt-1">{{ $periodLabel }}</p>
            </div>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <select name="month" onchange="this.form.submit()" class="form-input !w-auto !min-w-0 !px-4 !py-1.5 !text-xs">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->locale('ar')->isoFormat('MMMM') }}
                        </option>
                    @endforeach
                </select>
                <select name="year" onchange="this.form.submit()" class="form-input !w-auto !min-w-0 !px-4 !py-1.5 !text-xs">
                    @foreach(range(now()->year, now()->year - 2) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if(!auth()->user()->employee)
            <div class="alert-info">
                <p>حسابك غير مرتبط بملف موظف حتى الآن، برجاء التواصل مع الإدارة.</p>
            </div>
        @elseif(!$stats)
            <div class="alert-warning">
                <p>لا توجد بيانات حضور متاحة لهذا الشهر.</p>
            </div>
        @else
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="bg-emerald-50 rounded-xl p-3 text-center">
                    <p class="text-xl font-black text-emerald-600">{{ $stats['present'] }}</p>
                    <p class="text-xs text-emerald-700">حضور</p>
                </div>
                <div class="bg-red-50 rounded-xl p-3 text-center">
                    <p class="text-xl font-black text-red-500">{{ $stats['absent'] }}</p>
                    <p class="text-xs text-red-600">غياب</p>
                </div>
                <div class="bg-amber-50 rounded-xl p-3 text-center">
                    <p class="text-lg font-black text-amber-600">{{ floor($stats['late_minutes'] / 60) }}:{{ str_pad($stats['late_minutes'] % 60, 2, '0', STR_PAD_LEFT) }}</p>
                    <p class="text-xs text-amber-700">تأخير</p>
                </div>
                <div class="rounded-xl p-3 text-center" style="background: rgba(49,113,157,0.08);">
                    <p class="text-lg font-black" style="color: #31719d;">{{ floor($stats['overtime_minutes'] / 60) }}:{{ str_pad($stats['overtime_minutes'] % 60, 2, '0', STR_PAD_LEFT) }}</p>
                    <p class="text-xs" style="color: #31719d;">Overtime</p>
                </div>
            </div>
        @endif
    </div>

    @if(auth()->user()->employee && $dailyBreakdown->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="card-header">
                <h3 class="text-sm font-bold text-slate-700">جدول الحضور الخاص بك</h3>
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
                            @php $record = $day['record']; @endphp
                            <tr>
                                <td class="font-mono text-xs text-slate-600">{{ $day['date']->format('Y-m-d') }}</td>
                                <td class="text-sm text-slate-600">{{ $day['day_name'] }}</td>
                                <td class="text-center">
                                    @if($record && $record->clock_in)
                                        <span class="font-mono text-xs font-semibold text-slate-700">{{ substr($record->clock_in, 0, 5) }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($record && $record->clock_out)
                                        <span class="font-mono text-xs font-semibold text-slate-700">{{ substr($record->clock_out, 0, 5) }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($record && $record->late_minutes > 0)
                                        <span class="text-xs font-semibold text-amber-600">{{ floor($record->late_minutes / 60) }}:{{ str_pad($record->late_minutes % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                    @else
                                        <span class="text-slate-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($record && $record->overtime_minutes > 0)
                                        <span class="text-xs font-semibold" style="color:#31719d;">{{ floor($record->overtime_minutes / 60) }}:{{ str_pad($record->overtime_minutes % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                    @else
                                        <span class="text-slate-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td>
                                    @switch($day['status'])
                                        @case('present') <span class="badge-success">حاضر</span> @break
                                        @case('late') <span class="badge-warning">متأخر</span> @break
                                        @case('absent') <span class="badge-danger">غائب</span> @break
                                        @case('public_holiday') <span class="badge-info">إجازة رسمية</span> @break
                                        @case('friday') <span class="badge-gray">جمعة</span> @break
                                        @case('weekly_leave') <span class="badge" style="background:#ede9fe;color:#7c3aed;border:1px solid #ddd6fe;">إجازة أسبوعية</span> @break
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
