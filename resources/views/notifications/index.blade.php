@extends('layouts.app')

@section('title', 'الإشعارات')
@section('page-title', 'الإشعارات')
@section('page-subtitle', 'مراجعة كل الإشعارات الجديدة والسابقة')

@section('content')
@php
    $summary = $notificationSummary ?? ['total' => 0, 'unread' => 0];
    $readCount = max(0, (int) $summary['total'] - (int) $summary['unread']);
@endphp

<div class="space-y-5">
    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
        <div class="bg-gradient-to-l from-slate-900 via-slate-800 to-sky-900 px-5 py-6 text-white sm:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <!-- <div class="max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-white/65">Notification Center</p>
                    <h3 class="mt-2 text-2xl font-black">مركز الإشعارات الداخلي</h3>
                    <p class="mt-2 text-sm leading-6 text-white/75">كل التنبيهات المهمة في مكان واحد: الطلبات الجديدة، القرارات، المهام، ورسائل تفعيل الحساب.</p>
                </div> -->

                @if((int) $summary['unread'] > 0)
                    <form action="{{ route('notifications.read-all') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-black text-slate-900 transition hover:bg-slate-100" style="
    color: black;
    background-color: cornflowerblue;
">
                            تحديد الكل كمقروء
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid gap-3 border-t border-slate-100 bg-slate-50/80 p-4 sm:grid-cols-3 sm:p-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-bold text-slate-500">إجمالي الإشعارات</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ $summary['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-sky-100 bg-sky-50 p-4">
                <p class="text-xs font-bold text-sky-700">غير المقروء</p>
                <p class="mt-2 text-2xl font-black text-sky-900">{{ $summary['unread'] }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                <p class="text-xs font-bold text-emerald-700">المقروء</p>
                <p class="mt-2 text-2xl font-black text-emerald-900">{{ $readCount }}</p>
            </div>
        </div>
    </section>

    <section class="card overflow-hidden">
        @forelse($notifications as $notification)
            @include('notifications.partials.item', ['notification' => $notification, 'compact' => false])
        @empty
            <div class="px-6 py-14 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/>
                    </svg>
                </div>
                <h4 class="mt-5 text-lg font-black text-slate-800" >لا توجد إشعارات حتى الآن</h4>
                <p class="mt-2 text-sm text-slate-500">عند وصول تنبيه جديد سيظهر هنا تلقائياً ويمكنك فتحه ومتابعته مباشرة.</p>
            </div>
        @endforelse

        @if($notifications->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
