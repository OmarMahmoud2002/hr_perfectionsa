@extends('layouts.app')

@section('title', 'تفاصيل: ' . $employee->name)
@section('page-title', $employee->name)
@section('page-subtitle', 'AC-No: ' . $employee->ac_no)

@section('content')

{{-- Breadcrumb --}}
<nav class="breadcrumb">
    <a href="{{ route('employees.index') }}">الموظفين</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    <span class="text-slate-700 font-medium">{{ $employee->name }}</span>
</nav>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- الصف الأيمن: بيانات الموظف --}}
    <div class="space-y-5">

        {{-- بطاقة الموظف --}}
        <div class="card p-6">
            <div class="text-center mb-5">
                <div class="w-20 h-20 rounded-3xl flex items-center justify-center text-white text-3xl font-black mx-auto mb-3"
                     style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                    {{ mb_substr($employee->name, 0, 1) }}
                </div>
                <h2 class="font-bold text-xl text-slate-800">{{ $employee->name }}</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    <span class="font-mono bg-slate-100 px-2 py-0.5 rounded text-xs">{{ $employee->ac_no }}</span>
                </p>
                <div class="mt-2">
                    @if($employee->is_active && !$employee->trashed())
                        <span class="badge-success">نشط</span>
                    @else
                        <span class="badge-danger">معطّل</span>
                    @endif
                </div>
            </div>

            <div class="space-y-3 border-t border-slate-100 pt-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500">المرتب الأساسي</span>
                    <span class="text-sm font-bold text-slate-800">
                        {{ number_format($employee->basic_salary, 0) }}
                        <span class="text-xs font-normal text-slate-400">ج.م</span>
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500">تاريخ الإضافة</span>
                    <span class="text-xs text-slate-500">{{ $employee->created_at->format('Y-m-d') }}</span>
                </div>
            </div>

            {{-- شيفت العمل --}}
            <div class="mt-3 pt-3 border-t border-slate-100 space-y-2">
                <p class="text-xs font-semibold text-slate-500 mb-2">شيفت العمل</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500">الحضور</span>
                    <span class="text-xs font-semibold text-slate-700 font-mono">
                        {{ $employee->work_start_time ?? '09:00' }}
                        @if(!$employee->work_start_time)<span class="text-slate-400 font-normal">(افتراضي)</span>@endif
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500">الانصراف</span>
                    <span class="text-xs font-semibold text-slate-700 font-mono">
                        {{ $employee->work_end_time ?? '17:00' }}
                        @if(!$employee->work_end_time)<span class="text-slate-400 font-normal">(افتراضي)</span>@endif
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500">بدء الأوفرتايم</span>
                    <span class="text-xs font-semibold text-slate-700 font-mono">
                        {{ $employee->overtime_start_time ?? '17:30' }}
                        @if(!$employee->overtime_start_time)<span class="text-slate-400 font-normal">(افتراضي)</span>@endif
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500">فترة السماح</span>
                    <span class="text-xs font-semibold text-slate-700">
                        {{ $employee->late_grace_minutes ?? 30 }} دقيقة
                        @if($employee->late_grace_minutes === null)<span class="text-slate-400 font-normal">(افتراضي)</span>@endif
                    </span>
                </div>
            </div>

            @if(auth()->user()->isAdmin())
            <div class="mt-4 pt-4 border-t border-slate-100 flex gap-2">
                <a href="{{ route('employees.edit', $employee) }}" class="btn-gold flex-1 justify-center btn-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    تعديل
                </a>
                    <form action="{{ route('employees.destroy', $employee) }}" method="POST"
                        data-confirm="هل تريد تعطيل هذا الموظف؟"
                        data-confirm-title="تأكيد التعطيل"
                        data-confirm-btn="تعطيل"
                        data-confirm-type="warning">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger btn-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        تعطيل
                    </button>
                </form>
            </div>
            @endif
        </div>

        {{-- ملخص الشهر --}}
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-700">ملخص الشهر</h3>
                <form method="GET" class="flex items-center gap-1">
                    <select name="month" onchange="this.form.submit()" class="text-xs border-0 bg-slate-100 rounded-lg px-2 py-1 focus:ring-1 focus:ring-secondary-300">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->locale('ar')->isoFormat('MMMM') }}
                            </option>
                        @endforeach
                    </select>
                    <select name="year" onchange="this.form.submit()" class="text-xs border-0 bg-slate-100 rounded-lg px-2 py-1 focus:ring-1 focus:ring-secondary-300">
                        @foreach(range(now()->year, now()->year - 2) as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="bg-emerald-50 rounded-xl p-3 text-center">
                    <p class="text-2xl font-black text-emerald-600">{{ $stats['present'] }}</p>
                    <p class="text-xs text-emerald-700 mt-0.5">يوم حضور</p>
                </div>
                <div class="bg-red-50 rounded-xl p-3 text-center">
                    <p class="text-2xl font-black text-red-500">{{ $stats['absent'] }}</p>
                    <p class="text-xs text-red-600 mt-0.5">يوم غياب</p>
                </div>
                <div class="bg-amber-50 rounded-xl p-3 text-center">
                    <p class="text-xl font-black text-amber-600">
                        {{ floor($stats['late'] / 60) }}:{{ str_pad($stats['late'] % 60, 2, '0', STR_PAD_LEFT) }}
                    </p>
                    <p class="text-xs text-amber-700 mt-0.5">إجمالي التأخير</p>
                </div>
                <div class="rounded-xl p-3 text-center" style="background: rgba(49,113,157,0.08);">
                    <p class="text-xl font-black" style="color: #31719d;">
                        {{ floor($stats['overtime'] / 60) }}:{{ str_pad($stats['overtime'] % 60, 2, '0', STR_PAD_LEFT) }}
                    </p>
                    <p class="text-xs mt-0.5" style="color: #31719d;">إجمالي Overtime</p>
                </div>
            </div>

            @if($payroll)
            <div class="mt-3 pt-3 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500">الراتب الصافي</span>
                    <span class="text-lg font-black" style="color: #317c77;">
                        {{ number_format($payroll->net_salary, 0) }}
                        <span class="text-xs font-normal text-slate-400">ج.م</span>
                    </span>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- الصف الأيسر: سجل الحضور --}}
    <div class="lg:col-span-2">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h3 class="text-sm font-bold text-slate-700">سجل الحضور والانصراف</h3>
                <span class="badge-gray">{{ $employee->attendanceRecords->count() }} يوم</span>
            </div>

            @if($employee->attendanceRecords->count() > 0)
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>اليوم</th>
                            <th>الحضور</th>
                            <th>الانصراف</th>
                            <th>التأخير</th>
                            <th>Overtime</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employee->attendanceRecords as $record)
                        <tr class="{{ $record->is_absent ? 'bg-red-50/50' : '' }}">
                            <td class="font-mono text-xs text-slate-600">{{ $record->date->format('Y-m-d') }}</td>
                            <td class="text-xs text-slate-500">{{ $record->date->locale('ar')->isoFormat('dddd') }}</td>
                            <td>
                                @if($record->clock_in)
                                    <span class="{{ $record->late_minutes > 0 ? 'text-amber-600 font-semibold' : 'text-slate-700' }}">
                                        {{ substr($record->clock_in, 0, 5) }}
                                    </span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record->clock_out)
                                    <span class="{{ $record->overtime_minutes > 0 ? 'text-secondary-600 font-semibold' : 'text-slate-700' }}">
                                        {{ substr($record->clock_out, 0, 5) }}
                                    </span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record->late_minutes > 0)
                                    <span class="text-amber-600 font-semibold text-xs">
                                        {{ floor($record->late_minutes/60) > 0 ? floor($record->late_minutes/60).'س ' : '' }}{{ $record->late_minutes % 60 }}د
                                    </span>
                                @else
                                    <span class="text-slate-300 text-xs">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record->overtime_minutes > 0)
                                    <span class="font-semibold text-xs" style="color: #31719d;">
                                        {{ floor($record->overtime_minutes/60) > 0 ? floor($record->overtime_minutes/60).'س ' : '' }}{{ $record->overtime_minutes % 60 }}د
                                    </span>
                                @else
                                    <span class="text-slate-300 text-xs">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record->is_absent)
                                    <span class="badge-danger">غياب</span>
                                @elseif($record->notes && str_contains($record->notes, 'إجازة رسمية'))
                                    <span class="badge-teal">إجازة</span>
                                @elseif($record->late_minutes > 0)
                                    <span class="badge-warning">متأخر</span>
                                @else
                                    <span class="badge-success">حضر</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-slate-500 text-sm">لا توجد سجلات حضور لهذا الشهر</p>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('import.form') }}" class="mt-3 inline-block text-sm font-semibold" style="color: #4596cf;">
                    رفع ملف Excel →
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

@endsection
