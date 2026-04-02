@extends('layouts.app')

@section('title', 'مهامي')
@section('page-title', 'مهامي الشهرية')
@section('page-subtitle', 'المهام المسندة لك مع حالة التنفيذ والتقييم')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
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
            @php
                $today    = \Carbon\Carbon::today();
                $endDate  = $task->task_end_date ? \Carbon\Carbon::parse($task->task_end_date) : null;
                $daysLeft = $endDate ? $today->diffInDays($endDate, false) : null;
                $daysColor = ($daysLeft !== null && $daysLeft <= 1)
                    ? 'text-red-600 bg-red-50 border border-red-200'
                    : 'text-emerald-600 bg-emerald-50 border border-emerald-200';
                $assignedNames = $task->employees->pluck('name')->join('، ');
                $myAssignment = $task->assignments->first();
                $taskStatus = $myAssignment?->status ?? \App\Enums\TaskAssignmentStatus::ToDo;
                [$statusBadgeClass, $statusLabel] = match((string) $taskStatus->value) {
                    'in_progress' => ['bg-amber-100 text-amber-800 border border-amber-300', 'In Progress'],
                    'done' => ['bg-emerald-100 text-emerald-800 border border-emerald-300', 'Done'],
                    default => ['bg-slate-100 text-slate-700 border border-slate-300', 'To Do'],
                };
            @endphp
                <div class="card p-5 animate-slide-up">
                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-slate-800 leading-snug">{{ $task->title }}</h3>
                            @if($assignedNames)
                                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    مُسند إلى: <span class="font-medium text-slate-700">{{ $assignedNames }}</span>
                                </p>
                            @endif
                        </div>
                        <span class="{{ $task->evaluation ? 'badge-success' : 'badge-warning' }} flex-shrink-0">
                            {{ $task->evaluation ? 'مُقيّمة' : 'بدون تقييم' }}
                        </span>
                    </div>

                    {{-- Status --}}
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black {{ $statusBadgeClass }}">
                            {{ $statusLabel }}
                        </span>
                        <form method="POST" action="{{ route('tasks.my.status.update', $task) }}" class="flex items-center gap-2">
                            @csrf
                            @method('PATCH')
                            <label class="text-xs text-slate-500">الحالة</label>
                            <select name="status" class="form-input !h-8 !min-h-0 !py-1 !px-2.5 !text-xs !w-36">
                                <option value="to_do" {{ $taskStatus->value === 'to_do' ? 'selected' : '' }}>To Do</option>
                                <option value="in_progress" {{ $taskStatus->value === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="done" {{ $taskStatus->value === 'done' ? 'selected' : '' }}>Done</option>
                            </select>
                            <button type="submit" class="btn-primary btn-sm !h-8 !px-3 !text-xs">حفظ</button>
                        </form>
                    </div>

                    {{-- Dates + Countdown --}}
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <span>البداية: <span class="font-semibold text-slate-700">{{ optional($task->task_date)->format('Y-m-d') ?? '—' }}</span></span>
                        </div>
                        @if($task->task_end_date)
                            <span class="text-slate-300">|</span>
                            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M16 2v4M8 2v4M3 10h18"/><path stroke-linecap="round" stroke-width="2" d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/></svg>
                                <span>الانتهاء: <span class="font-semibold text-slate-700">{{ $task->task_end_date->format('Y-m-d') }}</span></span>
                            </div>
                            @if(!$task->evaluation)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $daysColor }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M12 6v6l4 2"/></svg>
                                    @if($daysLeft !== null)
                                        @if($daysLeft <= 0)
                                            انتهت
                                        @elseif($daysLeft === 1)
                                            يوم واحد متبقٍّ
                                        @else
                                            {{ $daysLeft }} يوم متبقٍّ
                                        @endif
                                    @endif
                                </span>
                            @endif
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
                                    <a href="{{ Storage::disk('public')->url($att->path) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 text-xs font-medium hover:bg-blue-100 transition">
                                        @if($att->is_image)
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/><circle cx="8.5" cy="8.5" r="1.5" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15l-5-5L5 21"/></svg>
                                        @else
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 002.828 2.828L18 9.828M7 7H4m0 0v3m0-3h3"/></svg>
                                        @endif
                                        {{ Str::limit($att->original_name, 20) }}
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

                    {{-- Score --}}
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
                        <div class="mt-4 rounded-xl {{ $scoreBg }} border {{ $scoreBorder }} p-3 flex items-center gap-4">
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
                    @else
                        <div class="mt-4 bg-slate-50 rounded-xl border border-slate-200 p-3 text-center">
                            <p class="text-xs text-slate-500">التقييم</p>
                            <p class="text-xl font-black mt-1 text-slate-300">—</p>
                        </div>
                    @endif

                    {{-- Notes --}}
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
