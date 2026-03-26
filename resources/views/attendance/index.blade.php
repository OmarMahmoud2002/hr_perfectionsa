@extends('layouts.app')

@section('title', 'تقارير الحضور')
@section('page-title', 'تقارير الحضور والانصراف')
@section('page-subtitle', 'اختر شهراً لعرض تقرير الحضور')

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

@if($batches->isEmpty())
{{-- لا توجد بيانات --}}
<div class="card p-12 text-center">
    <div class="w-20 h-20 rounded-3xl mx-auto mb-4 flex items-center justify-center" style="background: rgba(69,150,207,0.1);">
        <svg class="w-10 h-10" style="color: #4596cf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
    </div>
    <h3 class="text-lg font-bold text-slate-700 mb-2">لا توجد بيانات حضور بعد</h3>
    <p class="text-slate-500 text-sm mb-5">قم برفع ملف Excel أولاً لاستيراد بيانات الحضور.</p>
    @if(auth()->user()->isAdminLike())
    <a href="{{ route('import.form') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
        </svg>
        رفع ملف Excel
    </a>
    @endif
</div>

@else

<div class="section-header">
    <div>
        <h1 class="section-title">الشهور المتاحة</h1>
        <p class="section-subtitle">{{ $batches->count() }} شهر في النظام</p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @foreach($batches as $batch)
    @php
        $monthName = \Carbon\Carbon::create($batch->year, $batch->month, 1)->locale('ar')->isoFormat('MMMM');
    @endphp
    <a href="{{ route('attendance.report', ['month' => $batch->month, 'year' => $batch->year]) }}"
       class="card p-5 hover:shadow-card-hover transition-all duration-200 group cursor-pointer block">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 transition-all group-hover:scale-110"
                 style="background: linear-gradient(135deg, #4596cf22, #4d9b9722);">
                <svg class="w-6 h-6" style="color: #4596cf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-slate-800">{{ $monthName }} {{ $batch->year }}</p>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ $batch->employees_count }} موظف &bull; {{ $batch->records_count }} سجل
                </p>
            </div>
            <svg class="w-4 h-4 text-slate-300 group-hover:text-secondary-400 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </div>
    </a>
    @endforeach
</div>

@endif

@endsection
