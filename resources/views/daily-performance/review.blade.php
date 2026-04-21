@extends('layouts.app')

@section('title', 'تقييم الأداء اليومي')
@section('page-title', 'تقييم الأداء اليومي')
@section('page-subtitle', 'متابعة من سجل ومن لم يسجل يوميا مع تقييم من 5 نجوم')

@push('styles')
<style>
    .star-input {
        display: none;
    }

    .star-label {
        cursor: pointer;
        color: #cbd5e1;
        transition: transform .15s ease, color .15s ease;
    }

    .star-label:hover {
        transform: translateY(-1px) scale(1.06);
    }

    .star-group {
        direction: ltr;
    }

    .star-group .star-input:checked ~ .star-label,
    .star-group .star-label:hover,
    .star-group .star-label:hover ~ .star-label {
        color: #f59e0b;
    }
</style>
@endpush

@section('content')
<div class="space-y-5">
    <div class="card p-0 overflow-hidden relative animate-fade-in">
        <div class="absolute inset-0 opacity-95"
             style="background: radial-gradient(circle at 80% 20%, rgba(245,158,11,.20), transparent 35%), radial-gradient(circle at 18% 82%, rgba(45,212,191,.20), transparent 38%), linear-gradient(135deg, #155e75 0%, #0f766e 55%, #1d4ed8 100%);"></div>

        <div class="relative p-6 text-white space-y-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-white/70 mb-2">Daily Performance Review</p>
                <h2 class="text-2xl font-black">لوحة تقييم الأداء اليومي</h2>
                <p class="text-sm text-white/85 mt-1">فلتر بسرعة، راجع السجلات، ثم قيّم بنجوم واضحة وتعليق مختصر.</p>
            </div>

            <form method="GET" id="dailyPerformanceReviewFiltersForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                <input type="date" name="date" value="{{ $filters['date'] }}" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl">

                <select name="employee_id" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl">
                    <option value="">كل الموظفين</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ (string) $filters['employee_id'] === (string) $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>

                <select name="status" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl">
                    <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="submitted" {{ $filters['status'] === 'submitted' ? 'selected' : '' }}>سجل الأداء</option>
                    <option value="not_submitted" {{ $filters['status'] === 'not_submitted' ? 'selected' : '' }}>لم يسجل</option>
                </select>
            </form>

            @if($filters['status'] !== 'all' || !empty($filters['employee_id']) || $filters['date'] !== now()->toDateString())
                <div>
                    <a href="{{ route('daily-performance.review.index') }}" class="btn-ghost btn-sm !h-10 !px-5 !text-sm">مسح الفلاتر</a>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
        <div class="card p-4 animate-slide-up">
            <p class="text-xs text-slate-500">إجمالي الموظفين</p>
            <p class="text-2xl font-black text-slate-800 mt-1">{{ $stats['total_employees'] }}</p>
        </div>
        <div class="card p-4 animate-slide-up">
            <p class="text-xs text-slate-500">المسجلون اليوم</p>
            <p class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['submitted_count'] }}</p>
        </div>
        <div class="card p-4 animate-slide-up">
            <p class="text-xs text-slate-500">لم يسجلوا</p>
            <p class="text-2xl font-black text-amber-600 mt-1">{{ $stats['not_submitted_count'] }}</p>
        </div>
        <div class="card p-4 animate-slide-up">
            <p class="text-xs text-slate-500">نسبة التسجيل</p>
            <p class="text-2xl font-black text-sky-600 mt-1">{{ $stats['submission_rate'] }}%</p>
        </div>
    </div>

    @if($cards->isEmpty())
        <div class="card p-10 text-center animate-fade-in">
            <p class="text-slate-500 font-semibold">لا توجد نتائج مطابقة للفلاتر الحالية.</p>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            @foreach($cards as $employee)
                @php
                    $entry = $employee->dailyPerformanceEntries->first();
                    $avatar = $employee->user?->profile?->avatar_path
                        ? route('media.avatar', ['path' => $employee->user->profile->avatar_path])
                        : null;
                    $myReview = $entry?->reviews?->firstWhere('reviewer_user_id', auth()->id());
                    $jobTitleLabel = $employee->job_title_label ?? 'غير محدد';
                    $summaryStars = $myReview ? str_repeat('★', (int) $myReview->rating) . str_repeat('☆', 5 - (int) $myReview->rating) : '—';
                @endphp

                <div class="card p-5 animate-slide-up {{ $entry ? 'border border-emerald-200 bg-emerald-50/30' : 'border border-amber-200 bg-amber-50/30' }}" x-data="{ openDetails: false }">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0 flex items-center justify-center text-white font-black"
                                 style="background: linear-gradient(135deg, #0ea5e9, #14b8a6);">
                                @if($avatar)
                                    <img src="{{ $avatar }}" alt="{{ $employee->name }}" class="w-full h-full object-cover">
                                @else
                                    {{ mb_substr($employee->name, 0, 1) }}
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-extrabold text-slate-800 truncate">{{ $employee->name }}</h3>
                                <p class="text-xs text-slate-500">{{ $jobTitleLabel }}</p>
                                <p class="text-xs text-amber-600 font-bold mt-1">تقييمك: {{ $summaryStars }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="{{ $entry ? 'badge-success' : 'badge-warning' }}">
                                {{ $entry ? 'سجل الأداء' : 'لم يسجل' }}
                            </span>
                            @if($entry)
                                <button type="button" @click="openDetails = !openDetails" class="btn-ghost btn-sm !h-8 !px-2" :title="openDetails ? 'إخفاء التفاصيل' : 'عرض التفاصيل'">
                                    <svg class="w-4 h-4 transition-transform" :class="openDetails ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    @if(!$entry)
                        <div class="mt-4 rounded-xl border border-dashed border-amber-300 bg-white p-4 text-sm text-slate-600">
                            لا يوجد سجل أداء لهذا الموظف في تاريخ {{ $filters['date'] }}.
                        </div>
                    @else
                        <div class="mt-4 space-y-3" x-show="openDetails" x-transition>
                            <div class="rounded-xl bg-white p-4 border border-slate-200">
                                <p class="text-xs text-slate-500">المشروع</p>
                                <p class="text-sm font-bold text-slate-800 mt-1">{{ $entry->project_name }}</p>

                                <p class="text-xs text-slate-500 mt-3">الوصف</p>
                                <p class="text-sm text-slate-700 leading-7 mt-1">{{ $entry->work_description }}</p>
                            </div>

                            <div class="rounded-xl bg-white p-4 border border-slate-200">
                                <p class="text-xs text-slate-500 mb-2">المرفقات</p>

                                @if($entry->attachments->isEmpty())
                                    <p class="text-sm text-slate-500">لا توجد مرفقات.</p>
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($entry->attachments as $attachment)
                                            @php
                                                $fileUrl = route('media.daily-performance.file', ['path' => $attachment->path]);
                                            @endphp
                                            <a href="{{ $fileUrl }}" target="_blank" class="btn-outline btn-sm !h-8 !px-3 !text-xs">
                                                {{ $attachment->is_image ? 'صورة' : 'ملف' }}: {{ \Illuminate\Support\Str::limit($attachment->original_name, 18) }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="rounded-xl bg-white p-4 border border-slate-200">
                                <p class="text-xs text-slate-500 mb-2">الروابط</p>

                                @if($entry->links->isEmpty())
                                    <p class="text-sm text-slate-500">لا توجد روابط.</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach($entry->links as $link)
                                            <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-sky-700 hover:text-sky-800 break-all block">
                                                {{ $link->url }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @if($entry->reviews->isNotEmpty())
                                <div class="rounded-xl bg-white p-4 border border-slate-200">
                                    <p class="text-xs text-slate-500 mb-2">آخر التقييمات</p>
                                    <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                        @foreach($entry->reviews->take(5) as $review)
                                            <div class="text-sm">
                                                <p class="font-semibold text-slate-700">{{ $review->reviewer?->name ?? 'مقيم' }} <span class="text-amber-500">{{ str_repeat('★', (int) $review->rating) }}</span></p>
                                                <p class="text-slate-600">{{ $review->comment ?: 'بدون تعليق' }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('daily-performance.review.upsert', $entry) }}" class="rounded-xl bg-white p-4 border border-slate-200 space-y-3" data-loading data-loading-text="جاري الحفظ...">
                                @csrf
                                <p class="text-sm font-extrabold text-slate-800">تقييمك</p>

                                <div class="flex items-center justify-between">
                                    <div class="star-group flex flex-row-reverse items-center gap-1">
                                        @for($star = 5; $star >= 1; $star--)
                                            <input class="star-input peer" type="radio" id="rating-{{ $entry->id }}-{{ $star }}" name="rating" value="{{ $star }}"
                                                   {{ (int) old('rating', $myReview?->rating ?? 0) === $star ? 'checked' : '' }} required>
                                            <label class="star-label text-2xl" for="rating-{{ $entry->id }}-{{ $star }}">★</label>
                                        @endfor
                                    </div>

                                    <span class="text-xs text-slate-500">من 1 إلى 5</span>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label">تعليق (اختياري)</label>
                                    <textarea name="comment" rows="2" class="form-input" maxlength="2000" placeholder="اكتب ملاحظة قصيرة على الأداء">{{ old('comment', $myReview?->comment) }}</textarea>
                                </div>

                                <button class="btn-primary btn-sm" type="submit">{{ $myReview ? 'تحديث التقييم' : 'حفظ التقييم' }}</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

    @endif
</div>
@endsection
