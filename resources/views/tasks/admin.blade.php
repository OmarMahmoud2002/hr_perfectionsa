@extends('layouts.app')

@section('title', 'إدارة المهام')
@section('page-title', 'إدارة المهام الشهرية')
@section('page-subtitle', 'إنشاء المهام وإسنادها ومتابعة التقييمات')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
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
                    <select name="month" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl" style="padding-right: 35px;">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->locale('ar')->isoFormat('MMMM') }}
                            </option>
                        @endforeach
                    </select>

                    <select name="year" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl" style="padding-right: 35px;">
                        @foreach(range(now()->year, now()->year - 4) as $y)
                            <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>

                    <select name="employee_id" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl" style="padding-right: 35px;">
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

    <div x-show="openCreate" x-transition class="animate-slide-up" x-data="{
        links: [''],
        addLink() { this.links.push(''); },
        removeLink(i) { this.links.splice(i, 1); },
        fileNames: [],
        handleFiles(e) {
            this.fileNames = [];
            for (let f of e.target.files) { this.fileNames.push(f.name); }
        }
    }">
        {{-- Card Header --}}
        <div class="rounded-2xl overflow-hidden shadow-lg border border-slate-200 bg-white">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-gradient-to-l from-slate-50 to-white">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#2e6d98,#2f7c77);">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">إنشاء مهمة جديدة</h3>
                        <p class="text-xs text-slate-400">أملأ البيانات وأرفق الملفات والروابط</p>
                    </div>
                </div>
                <button type="button" @click="openCreate = false" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('tasks.admin.store') }}" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf
                <input type="hidden" name="period_month" value="{{ $month }}">
                <input type="hidden" name="period_year" value="{{ $year }}">

                {{-- Section 1: Basic Info --}}
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">معلومات المهمة</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2 form-group mb-0">
                            <label class="form-label" for="title">
                                <span class="text-red-500 ml-1">*</span>اسم المهمة
                            </label>
                            <input id="title" name="title" type="text" class="form-input" required maxlength="255"
                                   placeholder="أدخل عنوان المهمة...">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label" for="task_date">
                                <span class="text-red-500 ml-1">*</span>تاريخ البداية
                            </label>
                            <div class="relative">
                                <input id="task_date" name="task_date" type="date" class="form-input ltr-input" required
                                       value="{{ old('task_date', now()->toDateString()) }}">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label" for="task_end_date">
                                <span class="text-red-500 ml-1">*</span>تاريخ الانتهاء
                            </label>
                            <div class="relative">
                                <input id="task_end_date" name="task_end_date" type="date" class="form-input ltr-input" required
                                       value="{{ old('task_end_date', now()->toDateString()) }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Assign Employees --}}
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">إسناد الموظفين</p>
                    <div class="form-group mb-0">
                        <label class="form-label" for="employee_ids">
                            <span class="text-red-500 ml-1">*</span>اختر الموظفين
                        </label>
                        <div class="rounded-xl border border-slate-200 p-2 max-h-56 overflow-y-auto space-y-1.5">
                            @foreach($employees as $employee)
                                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 cursor-pointer hover:bg-slate-50 transition">
                                    <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}"
                                           class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-300"
                                           @checked(in_array((int) $employee->id, old('employee_ids', []), true))>
                                    <span class="flex-1 text-sm text-slate-700">
                                        <span class="font-semibold text-slate-800">{{ $employee->name }}</span>
                                        <span class="text-slate-500"> — {{ $employee->position_line }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="form-hint">اختر موظفًا واحدًا على الأقل.</p>
                    </div>
                </div>

                {{-- Section 3: Description --}}
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">الوصف</p>
                    <div class="form-group mb-0">
                        <label class="form-label" for="description">وصف المهمة <span class="text-slate-400 font-normal">(اختياري)</span></label>
                        <textarea id="description" name="description" rows="3" class="form-input" maxlength="2000"
                                  placeholder="اكتب تفاصيل المهمة ومتطلباتها..."></textarea>
                    </div>
                </div>

                {{-- Section 4: File Attachments --}}
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">المرفقات</p>
                    <label for="attachments"
                           class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-slate-300 rounded-2xl cursor-pointer bg-slate-50 hover:bg-slate-100 hover:border-primary-400 transition-all group">
                        <div class="flex flex-col items-center justify-center gap-2 text-center px-4">
                            <div class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center group-hover:bg-primary-200 transition">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 0L9 7m3-3l3 3"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-slate-600">اضغط لرفع الملفات</p>
                            <p class="text-xs text-slate-400">صور، PDF، Word، Excel، ZIP — حتى 20 ميجا للملف</p>
                        </div>
                        <input id="attachments" name="attachments[]" type="file" multiple class="hidden"
                               accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
                               @change="handleFiles($event)">
                    </label>
                    <template x-if="fileNames.length > 0">
                        <div class="mt-3 flex flex-wrap gap-2">
                            <template x-for="name in fileNames" :key="name">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary-50 border border-primary-200 text-primary-700 text-xs font-semibold">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 002.828 2.828L18 9.828M7 7H4m0 0v3m0-3h3"/></svg>
                                    <span x-text="name"></span>
                                </span>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Section 5: Links --}}
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">الروابط</p>
                    <div class="space-y-2">
                        <template x-for="(link, i) in links" :key="i">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 relative">
                                    <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 015.656 5.656l-3 3a4 4 0 01-5.657-5.657l1.5-1.5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.172 13.828a4 4 0 01-5.656-5.656l3-3a4 4 0 015.657 5.657l-1.5 1.5"/></svg>
                                    </span>
                                    <input type="url" name="links[]" :value="link" @input="links[i] = $event.target.value"
                                           class="form-input !pr-9 ltr-input" placeholder="https://..." maxlength="2000">
                                </div>
                                <button type="button" @click="removeLink(i)"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl border border-red-200 text-red-400 hover:bg-red-50 hover:text-red-600 transition flex-shrink-0"
                                        x-show="links.length > 1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                        <button type="button" @click="addLink()"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-dashed border-secondary-400 text-secondary-600 text-sm font-semibold hover:bg-secondary-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            إضافة رابط آخر
                        </button>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                    <button type="submit" class="btn-primary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        حفظ المهمة
                    </button>
                    <button type="button" @click="openCreate = false" class="btn-ghost">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    @if($tasks->isEmpty())
        <div class="card p-10 text-center animate-fade-in">
            <p class="text-slate-500 font-semibold">لا توجد مهام في هذا الشهر بعد.</p>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            @foreach($tasks as $task)
                @php
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
                        <div class="flex items-center gap-2">
                            <span class="{{ $task->is_active ? 'badge-success' : 'badge-gray' }}">{{ $task->is_active ? 'نشطة' : 'موقوفة' }}</span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $summaryStatusClass }}">{{ $summaryStatusLabel }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mt-4">
                        <div class="bg-slate-50 rounded-xl p-2 text-center">
                            <p class="text-xs text-slate-500">المسند لهم</p>
                            <p class="font-black text-slate-800 mt-1">{{ $task->assignments_count }}</p>
                        </div>
                        @php
                            $adminSc = $task->evaluation ? (float) $task->evaluation->score : null;
                            [$adminBg, $adminText] = match(true) {
                                $adminSc === null    => ['bg-slate-50',    'text-slate-400'],
                                $adminSc >= 9        => ['bg-emerald-50',  'text-emerald-700'],
                                $adminSc >= 7.5      => ['bg-lime-50',     'text-lime-700'],
                                $adminSc >= 6        => ['bg-amber-50',    'text-amber-700'],
                                $adminSc >= 4        => ['bg-orange-50',   'text-orange-700'],
                                default              => ['bg-red-50',      'text-red-700'],
                            };
                        @endphp
                        <div class="{{ $adminBg }} rounded-xl p-2 text-center">
                            <p class="text-xs text-slate-500">التقييم</p>
                            <p class="font-black mt-1 {{ $adminText }}">
                                {{ $adminSc !== null ? number_format($adminSc, 1) : '—' }}
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
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 002.828 2.828L18 9.828M7 7H4m0 0v3m0-3h3"/></svg>
                                        @endif
                                        {{ Str::limit($att->original_name, 20) }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

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

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button type="button" class="btn-ghost btn-sm" @click="openEdit = !openEdit">تعديل</button>

                        <form method="POST" action="{{ route('tasks.admin.toggle', $task) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-sm {{ $task->is_active ? 'btn-danger' : 'btn-teal' }}">
                                {{ $task->is_active ? 'إيقاف' : 'تفعيل' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('tasks.admin.destroy', $task) }}"
                              data-confirm="هل تريد حذف هذه المهمة نهائياً؟ سيتم حذف التقييمات، الإسنادات، المرفقات والروابط المرتبطة بها ولا يمكن التراجع."
                              data-confirm-title="حذف نهائي للمهمة"
                              data-confirm-btn="حذف نهائي"
                              data-confirm-type="danger">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-sm btn-danger">حذف</button>
                        </form>
                    </div>

                    <div x-show="openEdit" x-transition class="mt-4 border-t border-slate-100 pt-4">
                        <form method="POST" action="{{ route('tasks.admin.update', $task) }}"
                              enctype="multipart/form-data" class="space-y-4"
                              x-data="{
                                  deletedAttachments: [],
                                  deletedLinks: [],
                                  newLinks: [],
                                  addLink() { this.newLinks.push('') },
                                  removeNewLink(i) { this.newLinks.splice(i, 1) }
                              }">
                            @csrf
                            @method('PUT')

                            {{-- Basic fields --}}
                            <div class="form-group mb-0">
                                <label class="form-label">اسم المهمة</label>
                                <input type="text" name="title" class="form-input" value="{{ $task->title }}" required maxlength="255">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">الوصف</label>
                                <textarea name="description" rows="2" class="form-input" maxlength="2000">{{ $task->description }}</textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="form-group mb-0">
                                    <label class="form-label">تاريخ المهمة</label>
                                    <input type="date" name="task_date" class="form-input" required
                                           value="{{ optional($task->task_date)->format('Y-m-d') ?? now()->toDateString() }}">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">تاريخ الانتهاء</label>
                                    <input type="date" name="task_end_date" class="form-input" required
                                           value="{{ optional($task->task_end_date)->format('Y-m-d') ?? optional($task->task_date)->format('Y-m-d') ?? now()->toDateString() }}">
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">الموظفون</label>
                                <div class="rounded-xl border border-slate-200 p-2 max-h-52 overflow-y-auto space-y-1.5">
                                    @foreach($employees as $emp)
                                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 cursor-pointer hover:bg-slate-50 transition">
                                            <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                                   class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-300"
                                                   @checked($task->employees->contains('id', $emp->id))>
                                            <span class="flex-1 text-sm text-slate-700">
                                                <span class="font-semibold text-slate-800">{{ $emp->name }}</span>
                                                <span class="text-slate-500"> — {{ $emp->position_line }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Existing Attachments --}}
                            @if($task->attachments->isNotEmpty())
                                <div>
                                    <label class="form-label">المرفقات الحالية</label>
                                    <div class="space-y-1.5">
                                        @foreach($task->attachments as $att)
                                            <div class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200"
                                                 x-data="{ removed: false }" x-show="!removed">
                                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 002.828 2.828L18 9.828"/></svg>
                                                                <a href="{{ route('media.task-attachment.file', ['path' => $att->path]) }}" target="_blank"
                                                   class="text-xs text-blue-600 hover:underline flex-1 truncate">{{ $att->original_name }}</a>
                                                <button type="button"
                                                        @click="removed = true; deletedAttachments.push({{ $att->id }})"
                                                        class="text-red-400 hover:text-red-600 transition flex-shrink-0" title="حذف">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <template x-for="id in deletedAttachments" :key="id">
                                <input type="hidden" name="delete_attachment_ids[]" :value="id">
                            </template>

                            {{-- New Attachments --}}
                            <div class="form-group mb-0">
                                <label class="form-label">إضافة مرفقات جديدة</label>
                                <input type="file" name="new_attachments[]" multiple
                                       accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
                                       class="form-input text-sm">
                                <p class="text-xs text-slate-400 mt-1">يمكنك اختيار عدة ملفات دفعة واحدة · الحد الأقصى 20MB لكل ملف</p>
                            </div>

                            {{-- Existing Links --}}
                            @if($task->links->isNotEmpty())
                                <div>
                                    <label class="form-label">الروابط الحالية</label>
                                    <div class="space-y-1.5">
                                        @foreach($task->links as $lnk)
                                            <div class="flex items-center gap-2"
                                                 x-data="{ removed: false }" x-show="!removed">
                                                <input type="url" name="existing_links[{{ $lnk->id }}]"
                                                       value="{{ $lnk->url }}"
                                                       class="form-input form-input-sm flex-1 text-xs ltr-input" maxlength="2000">
                                                <button type="button"
                                                        @click="removed = true; deletedLinks.push({{ $lnk->id }})"
                                                        class="text-red-400 hover:text-red-600 transition flex-shrink-0" title="حذف">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <template x-for="id in deletedLinks" :key="id">
                                <input type="hidden" name="delete_link_ids[]" :value="id">
                            </template>

                            {{-- New Links --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="form-label mb-0">إضافة روابط جديدة</label>
                                    <button type="button" @click="addLink()"
                                            class="text-xs text-primary-600 hover:text-primary-800 font-semibold flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        إضافة رابط
                                    </button>
                                </div>
                                <div class="space-y-1.5">
                                    <template x-for="(link, index) in newLinks" :key="index">
                                        <div class="flex items-center gap-2">
                                            <input type="url" name="new_links[]" x-model="newLinks[index]"
                                                   placeholder="https://example.com"
                                                   class="form-input form-input-sm flex-1 text-xs ltr-input" maxlength="2000">
                                            <button type="button" @click="removeNewLink(index)"
                                                    class="text-red-400 hover:text-red-600 transition flex-shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
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
