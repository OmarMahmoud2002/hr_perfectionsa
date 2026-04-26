@extends('layouts.app')

@section('title', $announcement->title)
@section('page-title', 'تفاصيل الإشعار')
@section('page-subtitle', 'عرض كامل للإشعار الداخلي والمرسل والمرفقات')

@section('content')
<div class="space-y-6">
    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
        <div class="px-5 py-6 sm:px-6"
             style="background:
                radial-gradient(circle at top right, rgba(69,150,207,.16), transparent 32%),
                radial-gradient(circle at left center, rgba(77,155,151,.14), transparent 28%),
                linear-gradient(135deg, #f8fbff 0%, #f2f8f7 52%, #f8fafc 100%);">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-[11px] font-black text-sky-700">إشعار داخلي</span>
                        <span class="inline-flex rounded-full bg-white px-3 py-1 text-[11px] font-bold text-slate-600 shadow-sm">
                            {{ $announcement->created_at?->locale('ar')->isoFormat('D MMMM YYYY - h:mm A') }}
                        </span>
                    </div>
                    <h1 class="mt-3 text-2xl font-black leading-tight text-slate-900 sm:text-3xl">{{ $announcement->title }}</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-600">
                        تم إرسال هذا الإشعار بواسطة
                        <span class="font-black text-slate-800">{{ $announcement->sender?->name ?? 'الإدارة' }}</span>
                        <span class="text-slate-400">• {{ $senderRoleLabel }}</span>
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('notifications.index') }}" class="btn-ghost justify-center !rounded-2xl !border !border-slate-200 !bg-white">
                        الرجوع للإشعارات
                    </a>
                    @if(auth()->user()->isAdminLike())
                        <a href="{{ route('notifications.compose') }}" class="btn-primary justify-center !rounded-2xl">
                            إرسال إشعار جديد
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-12">
        <section class="card overflow-hidden xl:col-span-8">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h3 class="text-lg font-black text-slate-900">نص الإشعار</h3>
            </div>

            <div class="space-y-5 px-5 py-5 sm:px-6">
                <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                    <p class="whitespace-pre-line text-[15px] leading-8 text-slate-700">{{ $announcement->message }}</p>
                </div>

                @if($announcementImageUrl)
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-3">
                        <img src="{{ $announcementImageUrl }}" alt="{{ $announcement->title }}" class="max-h-[70vh] w-full object-contain">
                    </div>
                @endif

                @if($announcement->link_url)
                    <div class="rounded-3xl border border-sky-100 bg-sky-50/80 p-5">
                        <p class="text-sm font-black text-sky-900">رابط مرفق</p>
                        <a href="{{ $announcement->link_url }}" target="_blank" rel="noopener noreferrer"
                           class="mt-3 inline-flex max-w-full items-center rounded-2xl bg-white px-4 py-3 text-sm font-bold text-slate-800 shadow-sm ring-1 ring-sky-100">
                            <span class="truncate">{{ $announcement->link_url }}</span>
                        </a>
                    </div>
                @endif
            </div>
        </section>

        <aside class="space-y-5 xl:col-span-4">
            <section class="card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-black text-slate-900">بيانات الإرسال</h3>
                </div>

                <div class="space-y-4 px-5 py-5">
                    <div class="rounded-3xl border border-slate-200 bg-white p-4">
                        <p class="text-xs font-bold text-slate-500">أرسل بواسطة</p>
                        <p class="mt-2 text-base font-black text-slate-900">{{ $announcement->sender?->name ?? 'الإدارة' }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $senderRoleLabel }}</p>
                    </div>

                    <div class="rounded-3xl border border-emerald-100 bg-emerald-50 p-4">
                        <p class="text-xs font-bold text-emerald-700">إجمالي المستلمين</p>
                        <p class="mt-2 text-2xl font-black text-emerald-900">{{ $announcement->recipient_count }}</p>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-bold text-slate-500">الفئة المستهدفة</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse($audienceLabels as $label)
                                <span class="inline-flex rounded-full bg-white px-3 py-1.5 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                                    {{ $label }}
                                </span>
                            @empty
                                <span class="text-sm text-slate-500">كل الموظفين</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
