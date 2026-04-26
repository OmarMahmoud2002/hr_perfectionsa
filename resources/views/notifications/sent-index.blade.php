@extends('layouts.app')

@section('title', 'سجل الإشعارات المرسلة')
@section('page-title', 'سجل الإشعارات المرسلة')
@section('page-subtitle', 'متابعة كل الإشعارات التي تم إرسالها سابقًا')

@section('content')
@php
    $summary = $sentSummary ?? ['total' => 0, 'with_images' => 0];
@endphp

<div class="space-y-6">
    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
        <div class="px-5 py-6 sm:px-6"
             style="background:
                radial-gradient(circle at top right, rgba(69,150,207,.16), transparent 32%),
                radial-gradient(circle at left center, rgba(77,155,151,.14), transparent 28%),
                linear-gradient(135deg, #f8fbff 0%, #f2f8f7 52%, #f8fafc 100%);">
            <div class="grid gap-4 lg:grid-cols-[1.5fr_.9fr] lg:items-center">
                <div>
    
                    <h2 class="mt-3 text-2xl font-black text-slate-900">أرشيف الرسائل المرسلة</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">راجع كل الإشعارات السابقة، وابحث بالعنوان أو المرسل، وافتح أي رسالة لمشاهدة التفاصيل الكاملة.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 text-center shadow-sm">
                        <p class="text-[11px] font-bold text-slate-500">إجمالي الرسائل</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ $summary['total'] }}</p>
                    </div>
                    <!-- <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-4 text-center shadow-sm">
                        <p class="text-[11px] font-bold text-emerald-700">برسوم مرفقة</p>
                        <p class="mt-2 text-2xl font-black text-emerald-900">{{ $summary['with_images'] }}</p>
                    </div> -->
                </div>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-body">
            <form action="{{ route('notifications.sent.index') }}" method="GET" class="grid gap-3 sm:grid-cols-[1fr_auto]">
                <div>
                    <label for="search" class="form-label">بحث داخل السجل</label>
                    <input id="search" type="text" name="search" value="{{ $search }}"
                           class="form-input !rounded-2xl !border-slate-200 !bg-slate-50/80"
                           placeholder="ابحث بالعنوان أو نص الرسالة أو اسم المرسل">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary !rounded-2xl">بحث</button>
                    @if($search !== '')
                        <a href="{{ route('notifications.sent.index') }}" class="btn-ghost !rounded-2xl !border !border-slate-200 !bg-white">إعادة ضبط</a>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        @forelse($announcements as $announcement)
            @php
                $audienceLabels = (array) data_get($announcement->audience_meta, 'labels', []);
            @endphp
            <article class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-lg shadow-slate-200/50">
                <div class="space-y-4 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-lg font-black text-slate-900">{{ $announcement->title }}</p>
                            <p class="mt-1 text-sm text-slate-500">
                                بواسطة {{ $announcement->sender?->name ?? 'الإدارة' }}
                                <span class="mx-1 text-slate-300">•</span>
                                {{ $announcement->created_at?->locale('ar')->isoFormat('D MMMM YYYY') }}
                            </p>
                        </div>
                        <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-[11px] font-black text-sky-700">
                            {{ $announcement->recipient_count }} مستلم
                        </span>
                    </div>

                    <p class="text-sm leading-7 text-slate-600">{{ \Illuminate\Support\Str::limit($announcement->message, 180) }}</p>

                    <div class="flex flex-wrap gap-2">
                        @forelse(array_slice($audienceLabels, 0, 4) as $label)
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600">{{ $label }}</span>
                        @empty
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600">كل الموظفين</span>
                        @endforelse
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-2">
                        <a href="{{ route('notifications.announcements.show', $announcement) }}" class="btn-primary !rounded-2xl">
                            فتح التفاصيل
                        </a>
                        <a href="{{ route('notifications.compose') }}" class="btn-ghost !rounded-2xl !border !border-slate-200 !bg-white">
                            إرسال مشابه
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="xl:col-span-2">
                <div class="card p-10 text-center">
                    <h3 class="text-lg font-black text-slate-800">لا توجد رسائل مرسلة بعد</h3>
                    <p class="mt-2 text-sm text-slate-500">بمجرد إرسال أول إشعار سيظهر هنا داخل السجل.</p>
                </div>
            </div>
        @endforelse
    </section>

    @if($announcements->hasPages())
        <div class="card px-5 py-4">
            {{ $announcements->links() }}
        </div>
    @endif
</div>
@endsection
