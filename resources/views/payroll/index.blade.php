@extends('layouts.app')

@section('title', 'كشوف المرتبات')
@section('page-title', 'كشوف المرتبات')
@section('page-subtitle', 'حساب وعرض رواتب الموظفين الشهرية')

@section('content')

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

<div class="section-header">
    <div>
        <h1 class="section-title">كشوف المرتبات</h1>
        <p class="section-subtitle">{{ $payrollMonths->count() }} شهر محسوب</p>
    </div>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('payroll.calculate.form') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        حساب مرتبات جديدة
    </a>
    @endif
</div>

@if($payrollMonths->isEmpty())
{{-- لا توجد كشوف بعد --}}
<div class="card p-12 text-center">
    <div class="w-20 h-20 rounded-3xl mx-auto mb-4 flex items-center justify-center" style="background: rgba(77,155,151,0.1);">
        <svg class="w-10 h-10" style="color: #4d9b97;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <h3 class="text-lg font-bold text-slate-700 mb-2">لم يتم حساب أي رواتب بعد</h3>
    <p class="text-slate-500 text-sm mb-5">ابدأ بحساب رواتب الموظفين لأي شهر متاح.</p>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('payroll.calculate.form') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        احسب الآن
    </a>
    @endif
</div>

@else

{{-- الشهور المحسوبة --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    @foreach($payrollMonths as $pm)
    @php
        $monthName = \Carbon\Carbon::create($pm->year, $pm->month, 1)->locale('ar')->isoFormat('MMMM YYYY');
    @endphp
    <div class="card p-5 hover:shadow-card-hover transition-all duration-200">
        <div class="flex items-start justify-between mb-3">
            <div>
                <p class="font-bold text-slate-800">{{ $monthName }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $pm->employee_count }} موظف</p>
            </div>
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: rgba(77,155,151,0.12);">
                <svg class="w-5 h-5" style="color: #4d9b97;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-2xl font-black" style="color: #317c77;">
            {{ number_format($pm->total_net, 0) }}
            <span class="text-sm font-normal text-slate-400">ج.م</span>
        </p>
        <p class="text-xs text-slate-500 mb-3">إجمالي صافي المرتبات</p>
        <a href="{{ route('payroll.report', [$pm->month, $pm->year]) }}"
           class="btn-teal w-full justify-center btn-sm">
            عرض الكشف
        </a>
    </div>
    @endforeach
</div>

@endif

{{-- الشهور المتاحة للحساب (تم رفع ملف ولم يُحسب راتبها) --}}
@if($importedBatches->isNotEmpty())
@php
    $computedKeys = $payrollMonths->map(fn($p) => "{$p->month}-{$p->year}")->all();
    $pending = $importedBatches->filter(fn($b) => !in_array("{$b->month}-{$b->year}", $computedKeys));
@endphp

@if($pending->isNotEmpty())
<div class="card">
    <div class="card-header">
        <h3 class="text-sm font-bold text-slate-700">شهور لم يُحسب راتبها بعد</h3>
        <span class="badge-warning">{{ $pending->count() }}</span>
    </div>
    <div class="card-body">
        <div class="flex flex-wrap gap-3">
            @foreach($pending as $batch)
            <a href="{{ route('payroll.calculate.form', ['month' => $batch->month, 'year' => $batch->year]) }}"
               class="flex items-center gap-2 px-4 py-2 rounded-xl border-2 border-dashed border-amber-300
                      text-amber-700 text-sm font-semibold hover:bg-amber-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                {{ \Carbon\Carbon::create($batch->year, $batch->month, 1)->locale('ar')->isoFormat('MMMM YYYY') }}
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif
@endif

@endsection
