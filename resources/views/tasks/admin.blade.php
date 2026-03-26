@extends('layouts.app')

@section('title', 'إدارة المهام')
@section('page-title', 'إدارة المهام الشهرية')
@section('page-subtitle', 'إنشاء المهام وإسنادها ومتابعة التقييمات')

@section('content')
@php
    $monthName = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY');
@endphp

<div class="space-y-5" x-data="{ openCreate: false }">
    <div class="card p-0 overflow-hidden relative animate-fade-in">
        <div class="absolute inset-0 opacity-95"
             style="background: radial-gradient(circle at 80% 20%, rgba(231,197,57,.20), transparent 40%), radial-gradient(circle at 10% 85%, rgba(77,155,151,.25), transparent 42%), linear-gradient(140deg, #2e6d98 0%, #2f7c77 100%);"></div>

        <div class="relative p-6 sm:p-7 text-white space-y-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-white/70 mb-2">Tasks Control Panel</p>
                <h2 class="text-2xl sm:text-3xl font-black">مهام دورة {{ $monthName }}</h2>
                <p class="text-sm text-white/80 mt-2">دورة الراتب: 22 من الشهر السابق حتى 21 من الشهر الحالي.</p>
            </div>

            <form method="GET" id="adminTaskFiltersForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
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

                    <select name="employee_id" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl">
                        <option value="">اسم الموظف</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ (int) $employeeId === (int) $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                    <input name="task_date" type="date" value="{{ $taskDate }}" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl">
            </form>

            @if($taskDate || (int) $employeeId > 0)
                <div>
                    <a href="{{ route('tasks.admin.index', ['month' => $month, 'year' => $year]) }}" class="btn-ghost btn-sm !h-10 !px-5 !text-sm">مسح الفلاتر</a>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tasks.admin.export', ['month' => $month, 'year' => $year, 'task_date' => $taskDate, 'employee_id' => $employeeId]) }}" class="btn-outline btn-sm bg-white/95 !h-9 !text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-8m0 8l-3-3m3 3l3-3M5 20h14"/>
                    </svg>
                    تحميل Excel
                </a>

                <button type="button" class="btn-primary btn-sm !h-9 !text-xs" @click="openCreate = !openCreate">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    مهمة جديدة
                </button>
            </div>
        </div>
    </div>

    <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div class="card p-4 animate-slide-up" style="animation-delay:40ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">إجمالي المهام</p>
            <p class="text-2xl font-black text-slate-800 mt-1">{{ $totalTasks }}</p>
        </div>
        <div class="card p-4 animate-slide-up" style="animation-delay:80ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">المهام المقيمة</p>
            <p class="text-2xl font-black text-emerald-600 mt-1">{{ $evaluatedTasks }}</p>
        </div>
        <div class="card p-4 animate-slide-up" style="animation-delay:120ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">نسبه المهام المقيمة</p>
            <p class="text-2xl font-black text-secondary-700 mt-1">{{ number_format($coverage, 2) }}%</p>
        </div>
        <div class="card p-4 animate-slide-up" style="animation-delay:160ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">متوسط التقييمات</p>
            <p class="text-2xl font-black text-amber-600 mt-1">{{ number_format($averageEvaluationScore, 2) }}</p>
        </div>
    </div>

    <div x-show="openCreate" x-transition class="card p-6 animate-slide-up">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-slate-800">إنشاء مهمة جديدة</h3>
            <button type="button" class="btn-ghost btn-sm" @click="openCreate = false">إغلاق</button>
        </div>

        <form method="POST" action="{{ route('tasks.admin.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="period_month" value="{{ $month }}">
            <input type="hidden" name="period_year" value="{{ $year }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group mb-0">
                    <label class="form-label" for="title">اسم المهمة</label>
                    <input id="title" name="title" type="text" class="form-input" required maxlength="255">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" for="task_date">تاريخ المهمة</label>
                    <input id="task_date" name="task_date" type="date" class="form-input" required
                           value="{{ old('task_date', now()->toDateString()) }}">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" for="task_end_date">تاريخ انتهاء المهمة</label>
                    <input id="task_end_date" name="task_end_date" type="date" class="form-input" required
                           value="{{ old('task_end_date', now()->toDateString()) }}">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" for="employee_ids">إسناد لموظفين</label>
                    <select id="employee_ids" name="employee_ids[]" class="form-input" multiple size="5" required>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }} - {{ $employee->ac_no }}</option>
                        @endforeach
                    </select>
                    <p class="form-hint">اضغط Ctrl لاختيار أكثر من موظف.</p>
                </div>
            </div>

            <div class="form-group mb-0">
                <label class="form-label" for="description">وصف المهمة (اختياري)</label>
                <textarea id="description" name="description" rows="3" class="form-input" maxlength="2000"></textarea>
            </div>

            <button type="submit" class="btn-primary">حفظ المهمة</button>
        </form>
    </div>

    @if($tasks->isEmpty())
        <div class="card p-10 text-center animate-fade-in">
            <p class="text-slate-500 font-semibold">لا توجد مهام في هذا الشهر بعد.</p>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            @foreach($tasks as $task)
                <div class="card p-5 animate-slide-up" x-data="{ openEdit: false }">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">{{ $task->title }}</h3>
                            <p class="text-xs text-slate-500 mt-1">
                                تاريخ البداية: {{ optional($task->task_date)->format('Y-m-d') ?? '—' }}
                                | تاريخ الانتهاء: {{ optional($task->task_end_date)->format('Y-m-d') ?? '—' }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1">{{ $task->description ?: 'بدون وصف' }}</p>
                        </div>
                        <span class="{{ $task->is_active ? 'badge-success' : 'badge-gray' }}">{{ $task->is_active ? 'نشطة' : 'موقوفة' }}</span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mt-4">
                        <div class="bg-slate-50 rounded-xl p-2 text-center">
                            <p class="text-xs text-slate-500">المسند لهم</p>
                            <p class="font-black text-slate-800 mt-1">{{ $task->assignments_count }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-2 text-center">
                            <p class="text-xs text-slate-500">التقييم</p>
                            <p class="font-black mt-1 {{ $task->evaluation ? 'text-emerald-600' : 'text-slate-400' }}">
                                {{ $task->evaluation?->score ?? '—' }}
                            </p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-2 text-center">
                            <p class="text-xs text-slate-500">المقيّم</p>
                            <p class="font-black text-slate-700 mt-1 text-xs">{{ $task->evaluation?->evaluator?->name ?? '—' }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs font-semibold text-slate-500 mb-1">الموظفون المسند لهم</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($task->employees as $employee)
                                <span class="inline-flex items-center rounded-xl bg-secondary-100 text-secondary-800 px-3 py-1.5 text-sm font-black">{{ $employee->name }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button type="button" class="btn-ghost btn-sm" @click="openEdit = !openEdit">تعديل</button>

                        <form method="POST" action="{{ route('tasks.admin.toggle', $task) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-sm {{ $task->is_active ? 'btn-danger' : 'btn-teal' }}">
                                {{ $task->is_active ? 'إيقاف' : 'تفعيل' }}
                            </button>
                        </form>
                    </div>

                    <div x-show="openEdit" x-transition class="mt-4 border-t border-slate-100 pt-4">
                        <form method="POST" action="{{ route('tasks.admin.update', $task) }}" class="space-y-3">
                            @csrf
                            @method('PUT')

                            <div class="form-group mb-0">
                                <label class="form-label">اسم المهمة</label>
                                <input type="text" name="title" class="form-input" value="{{ $task->title }}" required maxlength="255">
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label">الوصف</label>
                                <textarea name="description" rows="2" class="form-input" maxlength="2000">{{ $task->description }}</textarea>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label">تاريخ المهمة</label>
                                <input type="date" name="task_date" class="form-input" required
                                       value="{{ optional($task->task_date)->format('Y-m-d') ?? now()->toDateString() }}">
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label">تاريخ انتهاء المهمة</label>
                                <input type="date" name="task_end_date" class="form-input" required
                                       value="{{ optional($task->task_end_date)->format('Y-m-d') ?? optional($task->task_date)->format('Y-m-d') ?? now()->toDateString() }}">
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label">الموظفون</label>
                                <select name="employee_ids[]" class="form-input" multiple size="5" required>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ $task->employees->contains('id', $employee->id) ? 'selected' : '' }}>
                                            {{ $employee->name }} - {{ $employee->ac_no }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn-primary btn-sm">حفظ التعديل</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
