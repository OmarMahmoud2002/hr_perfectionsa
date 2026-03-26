@extends('layouts.app')

@section('title', 'تقييم المهام')
@section('page-title', 'تقييم المهام')
@section('page-subtitle', 'قيّم المهام الموكلة مع إمكانية الفلترة بالتاريخ')

@section('content')
@php
    $monthName = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY');
@endphp

<div class="space-y-5">
    <div class="card p-0 overflow-hidden relative animate-fade-in">
        <div class="absolute inset-0 opacity-95"
             style="background: radial-gradient(circle at 75% 15%, rgba(231,197,57,.20), transparent 35%), radial-gradient(circle at 15% 90%, rgba(77,155,151,.20), transparent 38%), linear-gradient(135deg, #2e6d98 0%, #2f7c77 100%);"></div>
        <div class="relative p-6 text-white space-y-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-white/70 mb-2">Evaluator Panel</p>
                <h2 class="text-2xl font-black">مهام {{ $monthName }}</h2>
                <p class="text-sm text-white/80 mt-1">يمكنك التعديل على تقييمك لنفس المهمة في أي وقت.</p>
            </div>

            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
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
                <input name="task_date" type="date" value="{{ $taskDate }}" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl">
            </form>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tasks.evaluator.export', ['month' => $month, 'year' => $year, 'task_date' => $taskDate]) }}" class="btn-outline btn-sm bg-white/95 !h-10 !px-5 !text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-8m0 8l-3-3m3 3l3-3M5 20h14"/>
                    </svg>
                    تحميل Excel
                </a>

                @if($taskDate)
                    <a href="{{ route('tasks.evaluator.index', ['month' => $month, 'year' => $year]) }}" class="btn-ghost btn-sm !h-10 !px-5 !text-sm">مسح الفلاتر</a>
                @endif
            </div>
        </div>
    </div>

    @if($tasks->isEmpty())
        <div class="card p-10 text-center animate-fade-in">
            <p class="text-slate-500 font-semibold">لا توجد مهام متاحة للتقييم في هذه الفترة.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($tasks as $task)
                <div class="card p-5 animate-slide-up {{ $task->evaluation ? 'border border-emerald-200 bg-emerald-50/40' : '' }}" x-data="{ openForm: {{ $task->evaluation ? 'false' : 'true' }} }">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-bold text-slate-800">{{ $task->title }}</h3>
                        <div class="flex items-center gap-2">
                            @if($task->evaluation)
                                <button type="button" @click="openForm = !openForm" class="btn-ghost btn-sm !h-8 !px-2" title="تعديل التقييم">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2M4 20h4l10-10-4-4L4 16v4z"/>
                                    </svg>
                                </button>
                            @endif
                            <span class="{{ $task->evaluation ? 'badge-success' : 'badge-warning' }}">
                                {{ $task->evaluation ? 'تم التقييم' : 'بانتظار التقييم' }}
                            </span>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 mt-2">تاريخ المهمة: {{ optional($task->task_date)->format('Y-m-d') ?? '—' }}</p>

                    <p class="text-sm text-slate-600 mt-2 leading-7">{{ $task->description ?: 'لا يوجد وصف لهذه المهمة.' }}</p>

                    @if($task->evaluation)
                        <div class="mt-3 rounded-xl bg-white/80 border border-emerald-200 p-3">
                            <p class="text-xs text-slate-500">تقييمك الحالي</p>
                            <p class="text-lg font-black text-emerald-700">{{ $task->evaluation->score }}/10</p>
                            <p class="text-xs text-slate-500 mt-2">ملاحظتك</p>
                            <p class="text-sm text-slate-700">{{ $task->evaluation->note ?: 'لا توجد ملاحظة.' }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tasks.evaluator.upsert', $task) }}" class="mt-4 space-y-3" x-show="openForm" x-transition>
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="form-group mb-0">
                                <label class="form-label">التقييم (1 - 10)</label>
                                <input type="number" min="1" max="10" name="score" class="form-input ltr-input" required
                                       value="{{ old('score', $task->evaluation?->score ?? 1) }}">
                            </div>
                            <div class="sm:col-span-2 form-group mb-0">
                                <label class="form-label">ملاحظة (اختياري)</label>
                                <input type="text" name="note" maxlength="2000" class="form-input"
                                       value="{{ old('note', $task->evaluation?->note) }}"
                                       placeholder="اكتب ملاحظتك على تنفيذ المهمة">
                            </div>
                        </div>

                        <button class="btn-primary btn-sm" type="submit">{{ $task->evaluation ? 'تحديث التقييم' : 'حفظ التقييم' }}</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
