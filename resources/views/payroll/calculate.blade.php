@extends('layouts.app')

@php
    $monthName = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY');
@endphp

@section('title', 'حساب المرتبات — ' . $monthName)
@section('page-title', 'حساب المرتبات')
@section('page-subtitle', 'إدخال معدلات الحساب وتنفيذ حساب الرواتب')

@section('content')

{{-- Breadcrumb --}}
<nav class="breadcrumb">
    <a href="{{ route('payroll.index') }}">كشوف المرتبات</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    <span class="text-slate-700 font-medium">حساب جديد</span>
</nav>

{{-- Flash Messages --}}
@if(session('error'))
<div class="alert-error mb-5">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('error') }}
</div>
@endif

<form action="{{ route('payroll.calculate') }}" method="POST"
    data-loading="true" data-loading-target="#calculate-btn" data-loading-text="جاري الحساب...">
    @csrf
    <input type="hidden" name="month" value="{{ $month }}">
    <input type="hidden" name="year" value="{{ $year }}">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- الجانب الأيمن: اختيار الشهر --}}
        <div class="space-y-5">

            {{-- اختيار الشهر --}}
            <div class="card">
                <div class="card-header">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center" style="background: rgba(69,150,207,0.15);">
                            <svg class="w-4 h-4" style="color: #4596cf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700">الشهر المحدد</h3>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-xl font-black text-slate-800 mb-1">{{ $monthName }}</p>
                    @if($batch)
                        <p class="text-xs text-emerald-600 flex items-center gap-1 mb-3">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            بيانات حضور متوفرة — {{ $employees->count() }} موظف
                        </p>
                    @else
                        <p class="text-xs text-red-500 flex items-center gap-1 mb-3">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            لا توجد بيانات حضور لهذا الشهر
                        </p>
                    @endif

                    {{-- اختيار شهر آخر --}}
                    @if($availableBatches->isNotEmpty())
                    <div>
                        <p class="text-xs text-slate-500 mb-2 font-medium">اختر شهراً آخر:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($availableBatches as $b)
                            <a href="{{ route('payroll.calculate.form', ['month' => $b->month, 'year' => $b->year]) }}"
                               class="text-xs px-2.5 py-1 rounded-lg transition-all
                                      {{ $month == $b->month && $year == $b->year
                                          ? 'bg-secondary-100 text-secondary-700 font-semibold'
                                          : 'bg-slate-100 text-slate-600 hover:bg-secondary-50 hover:text-secondary-600' }}">
                                {{ \Carbon\Carbon::create($b->year, $b->month, 1)->locale('ar')->isoFormat('MMM YY') }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- قائمة الموظفين --}}
            @if($employees->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3 class="text-sm font-bold text-slate-700">الموظفون</h3>
                    <span class="badge-blue">{{ $employees->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="divide-y divide-slate-100 max-h-64 overflow-y-auto">
                        @foreach($employees as $emp)
                        <div class="px-4 py-2.5 flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                 style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                                {{ mb_substr($emp->name, 0, 1) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-700 truncate">{{ $emp->name }}</p>
                                <p class="text-xs text-slate-400">{{ number_format($emp->basic_salary, 0) }} ج.م</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- الجانب الأيسر: نموذج الحساب --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- معدلات الحساب --}}
            <div class="card">
                <div class="card-header">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-gold-100">
                            <svg class="w-4 h-4 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-700">معدلات الحساب</h3>
                            <p class="text-xs text-slate-400">القيم مسحوبة من الإعدادات — يمكنك تعديلها لهذه الجلسة فقط</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

                        {{-- خصم التأخير --}}
                        <div class="form-group mb-0">
                            <label class="form-label">
                                خصم ساعة التأخير
                                <span class="form-hint">بالجنيه لكل ساعة</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="late_deduction_per_hour" step="0.01" min="0"
                                       value="{{ old('late_deduction_per_hour', $defaultRates['late_deduction_per_hour']) }}"
                                       class="form-input pl-12 @error('late_deduction_per_hour') border-red-400 @enderror">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">ج.م</span>
                            </div>
                            @error('late_deduction_per_hour')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- خصم الغياب --}}
                        <div class="form-group mb-0">
                            <label class="form-label">
                                خصم يوم الغياب
                                <span class="form-hint">بالجنيه لكل يوم</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="absent_deduction_per_day" step="0.01" min="0"
                                       value="{{ old('absent_deduction_per_day', $defaultRates['absent_deduction_per_day']) }}"
                                       class="form-input pl-12 @error('absent_deduction_per_day') border-red-400 @enderror">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">ج.م</span>
                            </div>
                            @error('absent_deduction_per_day')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- مكافأة Overtime --}}
                        <div class="form-group mb-0">
                            <label class="form-label">
                                مكافأة ساعة Overtime
                                <span class="form-hint">بالجنيه لكل ساعة</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="overtime_rate_per_hour" step="0.01" min="0"
                                       value="{{ old('overtime_rate_per_hour', $defaultRates['overtime_rate_per_hour']) }}"
                                       class="form-input pl-12 @error('overtime_rate_per_hour') border-red-400 @enderror">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">ج.م</span>
                            </div>
                            @error('overtime_rate_per_hour')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- المعادلة التوضيحية --}}
            <div class="card p-4 border-2 border-dashed border-secondary-200">
                <p class="text-xs font-bold text-secondary-600 mb-2 uppercase tracking-wide">معادلة الحساب</p>
                <div class="flex flex-wrap items-center gap-2 text-sm font-mono">
                    <span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-700">المرتب الأساسي</span>
                    <span class="text-red-400 font-bold text-base">−</span>
                    <span class="px-2 py-1 rounded-lg bg-red-50 text-red-700">خصم التأخير</span>
                    <span class="text-red-400 font-bold text-base">−</span>
                    <span class="px-2 py-1 rounded-lg bg-red-50 text-red-700">خصم الغياب</span>
                    <span class="text-emerald-500 font-bold text-base">+</span>
                    <span class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700">مكافأة OT</span>
                    <span class="text-slate-400 font-bold text-base">=</span>
                    <span class="px-2 py-1 rounded-lg font-bold" style="background: rgba(77,155,151,0.15); color: #317c77;">المرتب النهائي</span>
                </div>
            </div>

            {{-- نطاق الحساب --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="text-sm font-bold text-slate-700">نطاق الحساب</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        {{-- حساب جميع الموظفين --}}
                        <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all
                                      {{ old('mode', $employeeId > 0 ? 'single' : 'all') === 'all' ? 'border-secondary-400 bg-secondary-50' : 'border-slate-200 hover:border-secondary-200' }}"
                               id="mode-all-label">
                            <input type="radio" name="mode" value="all" class="mt-0.5 accent-secondary-500"
                                   {{ old('mode', $employeeId > 0 ? 'single' : 'all') === 'all' ? 'checked' : '' }}
                                   onchange="toggleModeSelection(this)">
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">جميع الموظفين</p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    حساب رواتب جميع الموظفين في {{ $monthName }} دفعة واحدة
                                    ({{ $employees->count() }} موظف)
                                </p>
                            </div>
                        </label>

                        {{-- حساب موظف واحد --}}
                        <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all
                                      {{ old('mode', $employeeId > 0 ? 'single' : 'all') === 'single' ? 'border-secondary-400 bg-secondary-50' : 'border-slate-200 hover:border-secondary-200' }}"
                               id="mode-single-label">
                            <input type="radio" name="mode" value="single" class="mt-0.5 accent-secondary-500"
                                   {{ old('mode', $employeeId > 0 ? 'single' : 'all') === 'single' ? 'checked' : '' }}
                                   onchange="toggleModeSelection(this)">
                            <div class="flex-1">
                                <p class="font-semibold text-slate-800 text-sm">موظف واحد</p>
                                <p class="text-xs text-slate-500 mt-0.5 mb-2">اختر موظفاً محدداً لحساب راتبه</p>

                                <div id="single-employee-select" class="{{ old('mode', $employeeId > 0 ? 'single' : 'all') === 'single' ? '' : 'hidden' }}">
                                    <select name="employee_id" class="form-input text-sm">
                                        <option value="">— اختر الموظف —</option>
                                        @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" {{ (old('employee_id', $employeeId) == $emp->id) ? 'selected' : '' }}>
                                            {{ $emp->name }} ({{ $emp->ac_no }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </label>
                    </div>
                    @error('mode') <p class="form-error mt-2">{{ $message }}</p> @enderror
                    @error('employee_id') <p class="form-error mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- أزرار الإجراء --}}
            <div class="flex items-center gap-3">
                @if($batch)
                <button type="submit" id="calculate-btn" class="btn-primary btn-lg flex-1 justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    حساب المرتبات
                </button>
                @else
                <button type="button" disabled class="btn-primary btn-lg flex-1 justify-center opacity-50 cursor-not-allowed">
                    لا توجد بيانات حضور
                </button>
                @endif
                <a href="{{ route('payroll.index') }}" class="btn-ghost btn-lg">إلغاء</a>
            </div>

        </div>
    </div>
</form>

@push('scripts')
<script>
function toggleModeSelection(radio) {
    const singleDiv = document.getElementById('single-employee-select');
    const allLabel    = document.getElementById('mode-all-label');
    const singleLabel = document.getElementById('mode-single-label');

    if (radio.value === 'single') {
        singleDiv.classList.remove('hidden');
        singleLabel.classList.add('border-secondary-400', 'bg-secondary-50');
        singleLabel.classList.remove('border-slate-200');
        allLabel.classList.remove('border-secondary-400', 'bg-secondary-50');
        allLabel.classList.add('border-slate-200');
    } else {
        singleDiv.classList.add('hidden');
        allLabel.classList.add('border-secondary-400', 'bg-secondary-50');
        allLabel.classList.remove('border-slate-200');
        singleLabel.classList.remove('border-secondary-400', 'bg-secondary-50');
        singleLabel.classList.add('border-slate-200');
    }
}
</script>
@endpush

@endsection
