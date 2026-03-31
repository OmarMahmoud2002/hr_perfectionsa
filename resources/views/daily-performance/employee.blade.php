@extends('layouts.app')

@section('title', 'الأداء اليومي')
@section('page-title', 'الأداء اليومي')
@section('page-subtitle', 'سجل إنجازك اليومي مع المرفقات وتقييمات المقيمين')

@push('styles')
<style>
    .daily-timeline-item {
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .daily-timeline-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(2, 132, 199, 0.15);
    }
</style>
@endpush

@section('content')
@php
    $entryAttachments = $entry?->attachments ?? collect();
    $reviews = $ratingSummary['reviews'] ?? collect();
    $selectedDateCarbon = \Carbon\Carbon::parse($selectedDate);
@endphp

<div class="space-y-5">
    <div class="card p-0 overflow-hidden relative animate-fade-in">
        <div class="absolute inset-0 opacity-95"
             style="background: radial-gradient(circle at 84% 15%, rgba(245,158,11,.20), transparent 35%), radial-gradient(circle at 14% 88%, rgba(14,165,233,.18), transparent 40%), linear-gradient(135deg, #0f766e 0%, #0369a1 65%, #1d4ed8 100%);"></div>

        <div class="relative p-6 text-white space-y-4">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-white/70 mb-2">Daily Performance</p>
                    <h2 class="text-2xl font-black">{{ $selectedDateCarbon->locale('ar')->isoFormat('dddd، D MMMM YYYY') }}</h2>
                    <p class="text-sm text-white/85 mt-1">سجل ما أنجزته اليوم وأرفق ما يدعم التنفيذ.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('daily-performance.employee.index', ['date' => $prevDate]) }}" class="btn-outline btn-sm bg-white/95 !h-10 !px-4 !text-sm">اليوم السابق</a>
                    @if(!$isToday)
                        <a href="{{ route('daily-performance.employee.index', ['date' => now()->toDateString()]) }}" class="btn-outline btn-sm bg-white/95 !h-10 !px-4 !text-sm">العودة لليوم</a>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl bg-white/15 border border-white/20 p-3 backdrop-blur-sm">
                    <p class="text-xs text-white/80">حالة اليوم</p>
                    <p class="text-lg font-black mt-1">{{ $entry ? 'تم التسجيل' : 'لم يتم التسجيل' }}</p>
                </div>
                <div class="rounded-xl bg-white/15 border border-white/20 p-3 backdrop-blur-sm">
                    <p class="text-xs text-white/80">عدد المرفقات</p>
                    <p class="text-lg font-black mt-1">{{ $entryAttachments->count() }}</p>
                </div>
                <div class="rounded-xl bg-white/15 border border-white/20 p-3 backdrop-blur-sm">
                    <p class="text-xs text-white/80">متوسط التقييم</p>
                    <p class="text-lg font-black mt-1">{{ $ratingSummary['average_rating'] !== null ? $ratingSummary['average_rating'].' / 5' : '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 space-y-5" x-data="{ openForm: {{ ($isToday && ($errors->any() || !$entry)) ? 'true' : 'false' }} }">
            <div class="card p-5 animate-slide-up">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h3 class="text-lg font-extrabold text-slate-800">
                        {{ $entry ? 'بطاقة الأداء اليومية' : ($isToday ? 'إضافة سجل الأداء' : 'لا يوجد سجل لهذا اليوم') }}
                    </h3>
                    <div class="flex items-center gap-2">
                        @if($entry)
                            <span class="badge-success">محفوظ</span>
                            @if($isToday)
                                <button type="button" @click="openForm = !openForm" class="btn-ghost btn-sm !h-8 !px-2" :title="openForm ? 'إخفاء نموذج التعديل' : 'تعديل السجل'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2M4 20h4l10-10-4-4L4 16v4z"/>
                                    </svg>
                                </button>
                            @endif
                        @elseif($isToday)
                            <span class="badge-warning">جديد</span>
                        @endif
                    </div>
                </div>

                {{-- Read-only banner for past days --}}
                @if(!$isToday)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 mb-4 flex items-center gap-2 text-sm text-amber-800">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-11a7 7 0 110 14A7 7 0 0112 4z"/>
                        </svg>
                        <span>عرض فقط — لا يمكن تسجيل أو تعديل أداء يوم سابق.</span>
                    </div>
                @endif

                @if($entry)
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 mb-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs text-emerald-700">المشروع</p>
                                <p class="text-sm font-black text-slate-800 mt-1 truncate">{{ $entry->project_name }}</p>
                            </div>
                            <span class="text-xs text-emerald-700 font-semibold">{{ $entry->work_date?->format('Y-m-d') ?? $selectedDate }}</span>
                        </div>
                        <p class="text-sm text-slate-700 leading-7 mt-3">{{ $entry->work_description }}</p>
                    </div>
                @endif

                {{-- Form is only shown when viewing TODAY --}}
                @if($isToday)
                <form method="POST" action="{{ route('daily-performance.employee.upsert') }}" enctype="multipart/form-data" class="space-y-4" data-loading data-loading-text="جاري الحفظ..." x-show="openForm" x-transition>
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group mb-0">
                            <label class="form-label">تاريخ الأداء</label>
                            <input type="date" name="work_date" class="form-input bg-slate-100 cursor-not-allowed"
                                   value="{{ now()->toDateString() }}" readonly>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label">اسم المشروع</label>
                            <input type="text" name="project_name" class="form-input" maxlength="255"
                                   value="{{ old('project_name', $entry?->project_name) }}" placeholder="مثال: نظام الحضور - وحدة التقارير" required>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label">وصف ما تم إنجازه</label>
                        <textarea name="work_description" rows="5" class="form-input" maxlength="5000"
                                  placeholder="اكتب ما تم تنفيذه اليوم بالتفصيل..." required>{{ old('work_description', $entry?->work_description) }}</textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label">مرفقات (صور أو ملفات)</label>
                        <input type="file" name="attachments[]" class="form-input" multiple
                               accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                        <p class="text-xs text-slate-500 mt-1">الحد الأقصى 5 ملفات، وحجم الملف الواحد حتى 10MB.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <button class="btn-primary" type="submit">{{ $entry ? 'تحديث السجل' : 'حفظ السجل' }}</button>
                        @if($entry)
                            <button type="button" class="btn-ghost" @click="openForm = false">إغلاق التعديل</button>
                        @else
                            <a href="{{ route('daily-performance.employee.index', ['date' => $selectedDate]) }}" class="btn-ghost">تحديث الصفحة</a>
                        @endif
                    </div>
                </form>
                @endif
            </div>

            <div class="card p-5 animate-slide-up">
                <h3 class="text-base font-extrabold text-slate-800 mb-4">مرفقات اليوم</h3>

                @if($entryAttachments->isEmpty())
                    <p class="text-sm text-slate-500">لا توجد مرفقات لهذا اليوم.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($entryAttachments as $attachment)
                            @php
                                $fileUrl = route('media.daily-performance.file', ['path' => $attachment->path]);
                            @endphp
                            <div class="rounded-xl border border-slate-200 p-3 bg-white">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white {{ $attachment->is_image ? 'bg-emerald-500' : 'bg-slate-500' }}">
                                        @if($attachment->is_image)
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9l-6-6H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 3v6h6"/></svg>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-slate-800 truncate">{{ $attachment->original_name }}</p>
                                        <p class="text-xs text-slate-500 mt-1">{{ $attachment->mime_type ?: 'ملف' }}</p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <a href="{{ $fileUrl }}" target="_blank" class="btn-outline btn-sm !h-8 !px-3 !text-xs">فتح</a>

                                            @if($isToday)
                                            <form method="POST" action="{{ route('daily-performance.employee.attachment.destroy', $attachment) }}" data-confirm data-confirm-title="حذف المرفق"
                                                  data-confirm-message="هل أنت متأكد من حذف هذا المرفق؟" data-confirm-type="danger" data-confirm-text="حذف" data-cancel-text="إلغاء">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-ghost btn-sm !h-8 !px-3 !text-xs text-red-600">حذف</button>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-5">
            <div class="card p-5 animate-slide-up">
                <h3 class="text-base font-extrabold text-slate-800 mb-4">آخر 7 أيام</h3>

                <div class="space-y-2">
                    @foreach($timeline as $item)
                        <a href="{{ route('daily-performance.employee.index', ['date' => $item['date']]) }}"
                           class="daily-timeline-item flex items-center justify-between rounded-xl border px-3 py-2 {{ $selectedDate === $item['date'] ? 'border-sky-300 bg-sky-50' : 'border-slate-200 bg-white' }}">
                            <div>
                                <p class="text-sm font-bold text-slate-700">{{ \Carbon\Carbon::parse($item['date'])->locale('ar')->isoFormat('ddd D/M') }}</p>
                                <p class="text-xs text-slate-500">{{ $item['date'] }}</p>
                            </div>
                            <span class="{{ $item['has_entry'] ? 'badge-success' : 'badge-warning' }}">
                                {{ $item['has_entry'] ? 'سجل' : 'لم يسجل' }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="card p-5 animate-slide-up">
        <h3 class="text-base font-extrabold text-slate-800 mb-3">تقييمات اليوم</h3>

        @if(($ratingSummary['reviews_count'] ?? 0) === 0)
            <p class="text-sm text-slate-500">لا توجد تقييمات على هذا السجل حتى الآن.</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($reviews as $review)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-bold text-slate-700">{{ $review->reviewer?->name ?? 'مقيم' }}</p>
                            <p class="text-amber-500 text-sm font-black">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}</p>
                        </div>
                        <p class="text-sm text-slate-600 mt-2 leading-7">{{ $review->comment ?: 'لا يوجد تعليق.' }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
