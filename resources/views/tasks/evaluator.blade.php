@extends('layouts.app')

@section('title', 'تقييم المهام')
@section('page-title', 'تقييم المهام')
@section('page-subtitle', 'قيّم المهام الموكلة مع إمكانية الفلترة بالتاريخ')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
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

            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
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
                <select name="status" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl">
                    <option value="" {{ !$status ? 'selected' : '' }}>📋 جميع المهام</option>
                    <option value="evaluated" {{ $status === 'evaluated' ? 'selected' : '' }}>✅ المهام المقيّمة</option>
                    <option value="not_evaluated" {{ $status === 'not_evaluated' ? 'selected' : '' }}>⏳ غير المقيّمة</option>
                </select>
            </form>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tasks.evaluator.export', ['month' => $month, 'year' => $year, 'task_date' => $taskDate]) }}" class="btn-outline btn-sm bg-white/95 !h-10 !px-5 !text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-8m0 8l-3-3m3 3l3-3M5 20h14"/>
                    </svg>
                    تحميل Excel
                </a>

                @if($taskDate || $status)
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
            @php
                $today      = \Carbon\Carbon::today();
                $endDate    = $task->task_end_date ? \Carbon\Carbon::parse($task->task_end_date) : null;
                $daysLeft   = $endDate ? $today->diffInDays($endDate, false) : null;
                $daysColor  = ($daysLeft !== null && $daysLeft <= 1)
                    ? 'text-red-600 bg-red-50 border border-red-200'
                    : 'text-emerald-600 bg-emerald-50 border border-emerald-200';
                $assignmentStatuses = $task->assignments
                    ->mapWithKeys(fn ($assignment) => [(int) $assignment->employee_id => (string) ($assignment->status?->value ?? 'to_do')]);
                $summaryStatus = $assignmentStatuses->contains('done')
                    ? 'done'
                    : ($assignmentStatuses->contains('in_progress') ? 'in_progress' : 'to_do');
                [$summaryStatusClass, $summaryStatusLabel] = match($summaryStatus) {
                    'in_progress' => ['bg-amber-100 text-amber-800 border border-amber-300', 'In Progress'],
                    'done' => ['bg-emerald-100 text-emerald-800 border border-emerald-300', 'Done'],
                    default => ['bg-slate-100 text-slate-700 border border-slate-300', 'To Do'],
                };
            @endphp
                <div class="card p-5 animate-slide-up {{ $task->evaluation ? 'border border-emerald-200 bg-emerald-50/40' : '' }}" x-data="{ openForm: {{ $task->evaluation ? 'false' : 'true' }} }">

                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-slate-800 leading-snug">{{ $task->title }}</h3>
                            @if($task->employees->isNotEmpty())
                                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    مُسند إلى: <span class="font-medium text-slate-700">{{ $task->employees->pluck('name')->join('، ') }}</span>
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if($task->evaluation)
                                <button type="button" @click="openForm = !openForm" class="btn-ghost btn-sm !h-8 !px-2" title="تعديل التقييم">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2M4 20h4l10-10-4-4L4 16v4z"/></svg>
                                </button>
                            @endif
                            <span class="{{ $task->evaluation ? 'badge-success' : 'badge-warning' }}">
                                {{ $task->evaluation ? 'تم التقييم' : 'بانتظار التقييم' }}
                            </span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $summaryStatusClass }}">
                                {{ $summaryStatusLabel }}
                            </span>
                        </div>
                    </div>

                    {{-- Dates + Countdown --}}
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M16 2v4M8 2v4M3 10h18"/></svg>
                            البداية: <span class="font-semibold text-slate-700">{{ optional($task->task_date)->format('Y-m-d') ?? '—' }}</span>
                        </div>
                        @if($endDate)
                            <span class="text-slate-300">|</span>
                            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M16 2v4M8 2v4M3 10h18"/></svg>
                                الانتهاء: <span class="font-semibold text-slate-700">{{ $endDate->format('Y-m-d') }}</span>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $daysColor }}">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M12 6v6l4 2"/></svg>
                                @if($daysLeft <= 0)
                                    انتهت
                                @elseif($daysLeft === 1)
                                    يوم واحد متبقٍ
                                @else
                                    {{ $daysLeft }} يوم متبقٍ
                                @endif
                            </span>
                        @endif
                    </div>

                    {{-- Description --}}
                    <p class="text-sm text-slate-600 mt-3 leading-7">{{ $task->description ?: 'لا يوجد وصف لهذه المهمة.' }}</p>

                    {{-- Attachments --}}
                    @if($task->attachments->isNotEmpty())
                        <div class="mt-3">
                            <p class="text-xs font-semibold text-slate-500 mb-1.5">المرفقات</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($task->attachments as $att)
                                                <a href="{{ route('media.task-attachment.file', ['path' => $att->path]) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 text-xs font-medium hover:bg-blue-100 transition">
                                        @if($att->is_image)
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/><circle cx="8.5" cy="8.5" r="1.5" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15l-5-5L5 21"/></svg>
                                        @else
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        @endif
                                        {{ Str::limit($att->original_name, 22) }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Links --}}
                    @if($task->links->isNotEmpty())
                        <div class="mt-3">
                            <p class="text-xs font-semibold text-slate-500 mb-1.5">الروابط</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($task->links as $link)
                                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-teal-50 border border-teal-200 text-teal-700 text-xs font-medium hover:bg-teal-100 transition">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 015.656 5.656l-3 3a4 4 0 01-5.657-5.657l1.5-1.5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.172 13.828a4 4 0 01-5.656-5.656l3-3a4 4 0 015.657 5.657l-1.5 1.5"/></svg>
                                        {{ Str::limit($link->url, 35) }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Current evaluation display --}}
                    @if($task->evaluation)
                    @php
                        $sc = (float) $task->evaluation->score;
                        [$scoreBg, $scoreBorder, $scoreText] = match(true) {
                            $sc >= 9   => ['bg-emerald-50',  'border-emerald-300', 'text-emerald-700'],
                            $sc >= 7.5 => ['bg-lime-50',     'border-lime-300',    'text-lime-700'],
                            $sc >= 6   => ['bg-amber-50',    'border-amber-300',   'text-amber-700'],
                            $sc >= 4   => ['bg-orange-50',   'border-orange-300',  'text-orange-700'],
                            default    => ['bg-red-50',      'border-red-300',     'text-red-700'],
                        };
                    @endphp
                        <div class="mt-3 rounded-xl {{ $scoreBg }} border {{ $scoreBorder }} p-3 flex items-center gap-4">
                            <div class="text-center min-w-[56px]">
                                <p class="text-[10px] text-slate-500 mb-0.5">التقييم</p>
                                <p class="text-2xl font-black {{ $scoreText }} leading-none">{{ number_format($sc, 1) }}</p>
                                <p class="text-[10px] text-slate-400">/10</p>
                            </div>
                            @if($task->evaluation->note)
                                <div class="border-r border-slate-200 pr-4 flex-1">
                                    <p class="text-[10px] text-slate-500 mb-0.5">ملاحظة</p>
                                    <p class="text-sm text-slate-700 leading-snug">{{ $task->evaluation->note }}</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Evaluation form --}}
                    <form method="POST" action="{{ route('tasks.evaluator.upsert', $task) }}" class="mt-4 space-y-3" x-show="openForm" x-transition>
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="form-group mb-0">
                                <label class="form-label">التقييم (1 - 10)</label>
                                <input type="number" min="1" max="10" step="0.5" name="score" class="form-input ltr-input" required
                                       value="{{ old('score', $task->evaluation?->score ?? '') }}"
                                       placeholder="مثال: 7.5">
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
