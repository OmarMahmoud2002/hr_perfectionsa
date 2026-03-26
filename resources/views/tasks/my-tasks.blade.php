@extends('layouts.app')

@section('title', 'مهامي')
@section('page-title', 'مهامي الشهرية')
@section('page-subtitle', 'المهام المسندة لك وتقييم كل مهمة')

@section('content')
@php
    $monthName = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY');
@endphp

<div class="space-y-5">
    <div class="card p-0 overflow-hidden relative animate-fade-in">
        <div class="absolute inset-0 opacity-95"
             style="background: radial-gradient(circle at 85% 25%, rgba(231,197,57,.20), transparent 35%), radial-gradient(circle at 15% 85%, rgba(77,155,151,.20), transparent 38%), linear-gradient(135deg, #2e6d98 0%, #2f7c77 100%);"></div>
        <div class="relative p-6 text-white space-y-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-white/70 mb-2">My Tasks</p>
                <h2 class="text-2xl font-black">مهامي في {{ $monthName }}</h2>
                <p class="text-sm text-white/80 mt-1">تابع حالة كل مهمة وتقييمها وملاحظاتها.</p>
            </div>

            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <select name="month" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->locale('ar')->isoFormat('MMMM') }}
                        </option>
                    @endforeach
                </select>
                <select name="year" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl">
                    @foreach(range(now()->year, now()->year - 4) as $y)
                        <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tasks.my.index') }}" class="btn-ghost btn-sm !h-10 !px-5 !text-sm">مسح الفلاتر</a>
                <a href="{{ route('tasks.my.index', ['month' => $month, 'year' => $year]) }}" class="btn-outline btn-sm bg-white/95 !h-10 !px-5 !text-sm">تحديث النتائج</a>
            </div>
        </div>
    </div>

    @if(!$employee)
        <div class="alert-warning">
            <p>حسابك غير مرتبط بموظف، برجاء التواصل مع الإدارة.</p>
        </div>
    @elseif($tasks->isEmpty())
        <div class="card p-10 text-center animate-fade-in">
            <p class="text-slate-500 font-semibold">لا توجد مهام مسندة لك في هذه الفترة.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($tasks as $task)
                <div class="card p-5 animate-slide-up">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-bold text-slate-800">{{ $task->title }}</h3>
                        <span class="{{ $task->evaluation ? 'badge-success' : 'badge-warning' }}">
                            {{ $task->evaluation ? 'مُقيّمة' : 'بدون تقييم' }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-500 mt-2">تاريخ المهمة: {{ optional($task->task_date)->format('Y-m-d') ?? '—' }}</p>

                    <p class="text-sm text-slate-600 mt-2 leading-7">{{ $task->description ?: 'لا يوجد وصف لهذه المهمة.' }}</p>

                    <div class="mt-4 bg-slate-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-slate-500">التقييم</p>
                            <p class="text-xl font-black mt-1 {{ $task->evaluation ? 'text-emerald-600' : 'text-slate-400' }}">
                                {{ $task->evaluation?->score ?? '—' }}
                            </p>
                    </div>

                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <p class="text-xs font-semibold text-slate-500 mb-1">الملاحظات</p>
                        <p class="text-sm text-slate-700 leading-7">
                            {{ $task->evaluation?->note ?: 'لا توجد ملاحظات على هذه المهمة حتى الآن.' }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
