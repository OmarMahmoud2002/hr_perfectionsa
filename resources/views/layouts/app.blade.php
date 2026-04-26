<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'نظام الحضور والانصراف') - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-slate-50 font-sans">

    @php
        $navUser = auth()->user()->loadMissing('profile');
        $navAvatarUrl = $navUser->profile?->avatar_path
            ? route('media.avatar', ['path' => $navUser->profile->avatar_path])
            : null;
        $notificationsTableExists = \Illuminate\Support\Facades\Schema::hasTable('notifications');
        $navUnreadNotificationsCount = $notificationsTableExists
            ? $navUser->unreadNotifications()->count()
            : 0;
        $navRecentNotifications = $notificationsTableExists
            ? $navUser->notifications()->latest()->limit(7)->get()
            : collect();
        $roleLabel = match ($navUser->role) {
            'admin' => 'مدير النظام',
            'manager' => 'مدير عام',
            'hr' => 'موارد بشرية',
            'department_manager' => 'مدير قسم',
            'employee' => 'موظف',
            'office_girl' => 'موظف',
            'user' => 'مقيّم',
            default => 'مستخدم',
        };
    @endphp

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        @include('layouts.sidebar')

        {{-- Overlay للموبايل --}}
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-10 hidden lg:hidden" onclick="toggleSidebar()"></div>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden min-w-0">

            {{-- Top Bar --}}
            <header class="bg-white shadow-sm z-10 border-b border-slate-100">
                <div class="flex items-center justify-between px-4 sm:px-6 py-3.5">

                    {{-- Right: Toggle + Page Title --}}
                    <div class="flex items-center gap-3">
                        <button onclick="toggleSidebar()" class="p-2 rounded-xl text-slate-500 hover:text-secondary-600 hover:bg-secondary-50 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        <img src="{{ asset('logo.png') }}" alt="Logo" class="h-9 w-auto flex-shrink-0">

                        <div>
                            <h2 class="text-base font-bold text-slate-800 leading-tight">@yield('page-title', 'لوحة التحكم')</h2>
                            @hasSection('page-subtitle')
                                <p class="text-xs text-slate-500">@yield('page-subtitle')</p>
                            @endif
                        </div>
                    </div>

                    {{-- Left: User info + logout --}}
                    <div class="flex items-center gap-3">

                        {{-- Notifications Bell --}}
                        <div class="relative" id="notifications-menu-root">
                            <button type="button"
                                    id="notifications-menu-button"
                                    class="relative flex h-12 w-12 items-center justify-center rounded-2xl border transition-all {{ $navUnreadNotificationsCount > 0 ? 'border-rose-200 bg-rose-50 text-rose-700 shadow-sm shadow-rose-100' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50 hover:text-secondary-600' }}"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                    title="الإشعارات">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/>
                                </svg>
                                @if($navUnreadNotificationsCount > 0)
                                    <span class="absolute -bottom-1 -left-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[9px] font-black flex items-center justify-center border-2 border-white shadow-lg shadow-red-300/70 z-10" style="
    position: absolute;
    right: 24px;
    bottom: -10px;
">
                                        {{ $navUnreadNotificationsCount > 99 ? '99+' : $navUnreadNotificationsCount }}
                                    </span>
                                @endif
                            </button>

                            <div id="notifications-menu-panel" class="hidden absolute left-0 mt-3 w-[420px] sm:w-[470px] max-w-[96vw] overflow-hidden rounded-[22px] border border-slate-200 bg-white shadow-2xl shadow-slate-300/40 z-50 flex flex-col max-h-[82vh]" style="
    border-radius: 5%;
">
                                <div class="border-b border-slate-100 px-3.5 py-3 bg-white">
                                    <div class="flex items-center justify-between gap-3">
                                        <h4 class="text-xl font-black text-slate-900 leading-none" style="
    padding-right: 20px;
">الإشعارات</h4>
                                        @if($navUnreadNotificationsCount > 0)
                                            <form action="{{ route('notifications.read-all') }}" method="POST">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center rounded-lg bg-sky-100 px-3 py-1.5 text-xs font-black text-slate-900 transition hover:bg-sky-200">
                                                    تحديد الكل كمقروء
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-slate-50/70 px-2.5 py-2.5 space-y-2.5" style="max-height: calc(82vh - 108px); -webkit-overflow-scrolling: touch; touch-action: pan-y;">
                                    @forelse($navRecentNotifications as $notification)
                                        @php
                                            $nData = $notification->data ?? [];
                                            $nTitle = (string) ($nData['title'] ?? 'إشعار جديد');
                                            $nMessage = (string) ($nData['message'] ?? '');
                                            $nUrl = (string) ($nData['url'] ?? '#');
                                            $nOpenUrl = $nUrl !== '#' ? route('notifications.open', ['notificationId' => $notification->id]) : '#';
                                            $nUnread = $notification->read_at === null;
                                            $nType = (string) ($nData['type'] ?? 'general');
                                            $nRelativeTime = $notification->created_at
                                                ? $notification->created_at->locale('ar')->diffForHumans()
                                                : '';

                                            $nTypeMeta = match ($nType) {
                                                'leave_request_submitted' => ['icon' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>', 'card' => 'border-amber-300 bg-amber-50/90', 'circle' => 'bg-amber-500 text-white'],
                                                'leave_request_decision' => ['icon' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>', 'card' => 'border-emerald-300 bg-emerald-50/90', 'circle' => 'bg-emerald-500 text-white'],
                                                'task_assigned' => ['icon' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V9m-5-4h5m0 0v5m0-5L10 14"/></svg>', 'card' => 'border-amber-300 bg-amber-50/90', 'circle' => 'bg-amber-500 text-white'],
                                                'task_completed' => ['icon' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>', 'card' => 'border-emerald-300 bg-emerald-50/90', 'circle' => 'bg-emerald-500 text-white'],
                                                'task_evaluated' => ['icon' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.037 3.193a1 1 0 00.95.69h3.357c.969 0 1.371 1.24.588 1.81l-2.716 1.973a1 1 0 00-.364 1.118l1.037 3.193c.3.921-.755 1.688-1.538 1.118l-2.716-1.973a1 1 0 00-1.176 0l-2.716 1.973c-.783.57-1.838-.197-1.538-1.118l1.037-3.193a1 1 0 00-.364-1.118L5.117 8.62c-.783-.57-.38-1.81.588-1.81h3.357a1 1 0 00.95-.69l1.037-3.193z"/></svg>', 'card' => 'border-cyan-300 bg-cyan-50/90', 'circle' => 'bg-cyan-500 text-white'],
                                                'announcement_broadcast' => ['icon' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m8-8L3 11l7 2 2 7L21 4z"/></svg>', 'card' => 'border-sky-300 bg-sky-50/95', 'circle' => 'bg-sky-500 text-white'],
                                                'daily_performance_reviewed' => ['icon' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m3 6V7m3 10v-3m3 7H6a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2v14a2 2 0 01-2 2z"/></svg>', 'card' => 'border-violet-300 bg-violet-50/90', 'circle' => 'bg-violet-500 text-white'],
                                                'welcome_employee' => ['icon' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M7 16.938A9 9 0 1117 16.938"/></svg>', 'card' => 'border-slate-300 bg-slate-100/95', 'circle' => 'bg-slate-400 text-white'],
                                                'employee_of_month_published' => ['icon' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.037 3.193a1 1 0 00.95.69h3.357c.969 0 1.371 1.24.588 1.81l-2.716 1.973a1 1 0 00-.364 1.118l1.037 3.193c.3.921-.755 1.688-1.538 1.118l-2.716-1.973a1 1 0 00-1.176 0l-2.716 1.973c-.783.57-1.838-.197-1.538-1.118l1.037-3.193a1 1 0 00-.364-1.118L5.117 8.62c-.783-.57-.38-1.81.588-1.81h3.357a1 1 0 00.95-.69l1.037-3.193z"/></svg>', 'card' => 'border-indigo-300 bg-indigo-50/90', 'circle' => 'bg-indigo-500 text-white'],
                                                'attendance_month_imported' => ['icon' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>', 'card' => 'border-blue-300 bg-blue-50/90', 'circle' => 'bg-blue-500 text-white'],
                                                default => ['icon' => '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 'card' => 'border-slate-300 bg-slate-100/90', 'circle' => 'bg-slate-500 text-white'],
                                            };
                                        @endphp

                                        <div class="rounded-xl border p-3 {{ $nTypeMeta['card'] }} {{ $nUnread ? 'shadow-sm' : '' }}" style="
    width: 295px;
        margin-bottom: 5px;

">
                                            <div class="flex items-start gap-2.5">
                                                <div class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full {{ $nTypeMeta['circle'] }}">
                                                    <span aria-hidden="true">{!! $nTypeMeta['icon'] !!}</span>
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-start gap-2">
                                                        <p class="text-[14px] leading-6 font-black text-slate-900 break-words">{{ $nTitle }}</p>
                                                        @if($nUnread)
                                                            <span class="mt-2 inline-flex h-2 w-2 flex-shrink-0 rounded-full bg-sky-600"></span>
                                                        @endif
                                                    </div>

                                                    @if($nMessage !== '')
                                                        <p class="mt-1 text-[12px] leading-6 text-slate-800 break-words" style="display:-webkit-box;line-clamp:2;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $nMessage }}</p>
                                                    @endif

                                                    <div class="mt-2.5 flex items-center justify-between gap-2">
                                                        @if($nOpenUrl !== '#')
                                                            <a href="{{ $nOpenUrl }}" class="inline-flex items-center rounded-lg bg-sky-100 px-3 py-1.5 text-xs font-black text-slate-900 transition hover:bg-sky-200">فتح</a>
                                                        @endif
                                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-700">
                                                            <span class="inline-flex h-2 w-2 rounded-full bg-sky-600"></span>
                                                            <span class="font-semibold whitespace-nowrap">{{ $nRelativeTime }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-6 text-center text-xs text-slate-500">
                                            لا توجد إشعارات حتى الآن
                                        </div>
                                    @endforelse
                                </div>

                                <div class="border-t border-slate-100 bg-white p-2.5">
                                    <a href="{{ route('notifications.index') }}" class="flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-center text-xs font-black text-slate-700 transition hover:bg-slate-50">
                                        عرض كل الإشعارات
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Current Date --}}
                        <div class="hidden md:flex items-center gap-1.5 text-xs text-slate-500 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ now()->locale('ar')->isoFormat('D MMMM YYYY') }}
                        </div>

                        {{-- User Avatar + Name --}}
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl overflow-hidden flex items-center justify-center text-white text-sm font-bold"
                                 style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                                @if($navAvatarUrl)
                                    <img src="{{ $navAvatarUrl }}" alt="{{ $navUser->name }}" class="w-full h-full object-cover">
                                @else
                                    {{ mb_substr($navUser->name, 0, 1) }}
                                @endif
                            </div>
                            <div class="hidden sm:block">
                                <p class="text-xs font-semibold text-slate-700 leading-tight">{{ $navUser->name }}</p>
                                <p class="text-xs text-slate-400">{{ $roleLabel }}</p>
                            </div>
                        </div>

                        {{-- Logout --}}
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="p-2 rounded-xl text-slate-500 hover:text-red-500 hover:bg-red-50 transition-all"
                                    title="تسجيل الخروج">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 scrollbar-thin">

                {{-- Flash Messages --}}
                @if (session('success'))
                    <div class="alert-success animate-slide-up" x-data="{ show: true }" x-show="show">
                        <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <p>{{ session('success') }}</p>
                        <button onclick="this.parentElement.style.display='none'" class="mr-auto text-emerald-500 hover:text-emerald-700 transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert-error animate-slide-up">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <p>{{ session('error') }}</p>
                        <button onclick="this.parentElement.style.display='none'" class="mr-auto text-red-500 hover:text-red-700 transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert-warning animate-slide-up">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <p>{{ session('warning') }}</p>
                        <button onclick="this.parentElement.style.display='none'" class="mr-auto text-amber-500 hover:text-amber-700 transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                @endif

                @if (session('info'))
                    <div class="alert-info animate-slide-up">
                        <svg class="w-5 h-5 text-sky-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <p>{{ session('info') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert-error animate-slide-up" role="alert" aria-live="polite">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5a1 1 0 012 0v2a1 1 0 11-2 0v-2zm0-6a1 1 0 012 0v3a1 1 0 11-2 0V7z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-semibold">تعذّر حفظ التغييرات، يرجى مراجعة الأخطاء التالية:</p>
                            <ul class="list-disc pr-5 text-sm mt-1 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div id="global-loading-overlay"
         class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm"
         aria-live="polite" aria-busy="true" role="status">
        <div class="rounded-2xl shadow-2xl px-6 py-5 border border-white/20 text-white min-w-[260px] max-w-[88vw]"
             style="background: radial-gradient(circle at 20% 20%, rgba(255,255,255,.14), transparent 45%), linear-gradient(135deg, #2e6d98 0%, #2f7c77 100%);">
            <div class="flex items-center gap-3">
                <div class="relative flex-shrink-0">
                    <span class="absolute inset-0 rounded-full bg-white/20 animate-ping"></span>
                    <svg class="relative w-6 h-6 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-30"></circle>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-100"></path>
                    </svg>
                </div>
                <div>
                    <p id="global-loading-text" class="text-sm font-black tracking-wide leading-5">جاري التحميل</p>
                    <p class="text-[11px] text-white/85 mt-0.5 leading-5">يرجى الانتظار لحظات...</p>
                </div>
            </div>
            <div class="mt-3 h-1.5 rounded-full bg-white/20 overflow-hidden" aria-hidden="true">
                <div class="h-full w-1/2 rounded-full bg-white/90 animate-pulse"></div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var _open = null;

        function $s()  { return document.getElementById('sidebar'); }
        function $ov() { return document.getElementById('sidebar-overlay'); }
        function isMobile() { return window.innerWidth < 1024; }

        function setSidebar(open, animate) {
            var s = $s(), ov = $ov();

            if (!animate) s.style.transition = 'none';

            if (open) {
                s.style.transform = 'translateX(0)';
                s.style.width     = '';
                if (isMobile()) ov.classList.remove('hidden');
            } else {
                s.style.transform = 'translateX(100%)';
                if (!isMobile()) s.style.width = '0';
                ov.classList.add('hidden');
            }

            _open = open;

            if (!animate) {
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        s.style.transition = '';
                    });
                });
            }
        }

        window.toggleSidebar = function () {
            setSidebar(!_open, true);
            if (!isMobile()) localStorage.setItem('sidebarOpen', String(_open));
        };

        document.addEventListener('DOMContentLoaded', function () {
            var desktop = !isMobile();
            var saved   = localStorage.getItem('sidebarOpen');
            var open    = desktop ? saved !== 'false' : false;
            setSidebar(open, false);

            window.addEventListener('resize', function () {
                if (!isMobile()) {
                    $ov().classList.add('hidden');
                    if (_open) $s().style.width = '';
                }
            });
        });
    })();
    </script>

    <script>
    (function () {
        var root = document.getElementById('notifications-menu-root');
        var button = document.getElementById('notifications-menu-button');
        var panel = document.getElementById('notifications-menu-panel');

        if (!root || !button || !panel) {
            return;
        }

        function closeMenu() {
            panel.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        }

        function openMenu() {
            panel.classList.remove('hidden');
            button.setAttribute('aria-expanded', 'true');
        }

        button.addEventListener('click', function (event) {
            event.stopPropagation();
            if (panel.classList.contains('hidden')) {
                openMenu();
            } else {
                closeMenu();
            }
        });

        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });
    })();
    </script>

    <script>
    (function () {
        function getOverlay() {
            return document.getElementById('global-loading-overlay');
        }

        function getOverlayText() {
            return document.getElementById('global-loading-text');
        }

        function showGlobalLoading() {
            var overlay = getOverlay();
            if (!overlay) return;

            var textEl = getOverlayText();
            if (textEl) textEl.textContent = 'جاري التحميل';

            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
        }

        function hideGlobalLoading() {
            var overlay = getOverlay();
            if (!overlay) return;
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }

        function decorateSubmitButton(form) {
            var btn = null;
            if (form.dataset.loadingTarget) {
                btn = form.querySelector(form.dataset.loadingTarget);
            }
            if (!btn) {
                btn = form.querySelector('button[type="submit"]');
            }
            if (!btn) return;

            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-wait');

            var text = 'جاري التحميل';
            btn.innerHTML = '<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10" class="opacity-25"></circle><path d="M4 12a8 8 0 018-8" class="opacity-75"></path></svg><span class="ml-2">' + text + '</span>';
        }

        function shouldHandleLink(link, event) {
            if (!link) return false;
            if (event.defaultPrevented) return false;
            if (event.button !== 0) return false;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
            if (link.hasAttribute('download')) return false;
            if ((link.getAttribute('target') || '').toLowerCase() === '_blank') return false;
            if (link.dataset.noLoading !== undefined) return false;

            var href = link.getAttribute('href') || '';
            if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;

            var url;
            try {
                url = new URL(link.href, window.location.href);
            } catch (e) {
                return false;
            }

            if (url.origin !== window.location.origin) return false;
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return false;

            return true;
        }

        document.addEventListener('DOMContentLoaded', function () {
            window.showGlobalLoading = showGlobalLoading;
            window.hideGlobalLoading = hideGlobalLoading;

            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (e.defaultPrevented) return;
                if (form.dataset.confirm && !form._confirmed) return;

                var reassignmentFlag = form.querySelector('[data-confirm-reassignment-flag]');
                if (reassignmentFlag && reassignmentFlag.value !== '1') {
                    return;
                }

                if (form.dataset.loadingSubmitted === '1') {
                    e.preventDefault();
                    return;
                }

                form.dataset.loadingSubmitted = '1';
                decorateSubmitButton(form);
                showGlobalLoading();
            }, true);

            document.addEventListener('click', function (e) {
                var link = e.target.closest('a[href]');
                if (!shouldHandleLink(link, e)) return;
                showGlobalLoading();
            }, true);

            window.addEventListener('pageshow', function () {
                hideGlobalLoading();
            });
        });
    })();
    </script>

    @stack('scripts')

    {{-- ===== Custom Confirm Modal (global) ===== --}}
    <div id="app-modal" style="display:none" role="dialog" aria-modal="true"
         class="fixed inset-0 z-[999] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="app-modal-backdrop"></div>
        <div id="app-modal-box"
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all duration-150">
            {{-- Icon --}}
            <div class="flex justify-center pt-6 pb-1">
                <div id="app-modal-icon" class="w-14 h-14 rounded-full flex items-center justify-center"></div>
            </div>
            {{-- Content --}}
            <div class="px-6 py-3 text-center">
                <h3 id="app-modal-title" class="text-lg font-bold text-slate-800 mb-1.5"></h3>
                <p id="app-modal-body" class="text-sm text-slate-500 leading-relaxed"></p>
            </div>
            {{-- Actions --}}
            <div class="flex gap-3 p-5 pt-2">
                <button id="app-modal-cancel" type="button"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-medium text-sm hover:bg-slate-50 transition-colors">
                    إلغاء
                </button>
                <button id="app-modal-confirm" type="button"
                        class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-sm text-white transition-colors bg-red-600 hover:bg-red-700">
                    تأكيد
                </button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var _cb = null;

        var icons = {
            danger:  '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
            warning: '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
            info:    '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        };
        var typeClasses = {
            danger:  { icon: 'bg-red-100 text-red-600',    btn: 'bg-red-600 hover:bg-red-700' },
            warning: { icon: 'bg-amber-100 text-amber-600', btn: 'bg-amber-500 hover:bg-amber-600' },
            info:    { icon: 'bg-blue-100 text-blue-600',   btn: 'bg-blue-600 hover:bg-blue-700' },
        };

        window.showConfirm = function (opts) {
            var type   = opts.type || 'danger';
            var cls    = typeClasses[type] || typeClasses.danger;
            var modal  = document.getElementById('app-modal');
            var box    = document.getElementById('app-modal-box');
            var iconEl = document.getElementById('app-modal-icon');
            var confirmBtn = document.getElementById('app-modal-confirm');
            var cancelBtn  = document.getElementById('app-modal-cancel');

            document.getElementById('app-modal-title').textContent = opts.title   || 'تأكيد';
            document.getElementById('app-modal-body').textContent  = opts.message || '';
            confirmBtn.textContent  = opts.confirmText || 'تأكيد';
            cancelBtn.textContent   = opts.cancelText  || 'إلغاء';
            confirmBtn.className    = 'flex-1 px-4 py-2.5 rounded-xl font-semibold text-sm text-white transition-colors ' + cls.btn;
            iconEl.className        = 'w-14 h-14 rounded-full flex items-center justify-center ' + cls.icon;
            iconEl.innerHTML        = icons[type] || icons.danger;

            // إخفاء زر تأكيد في وضع المعلومات فقط
            if (opts.infoOnly) {
                confirmBtn.style.display = 'none';
                cancelBtn.textContent    = opts.cancelText || 'حسناً';
                cancelBtn.className      = 'w-full px-4 py-2.5 rounded-xl font-semibold text-sm text-white transition-colors ' + cls.btn;
            } else {
                confirmBtn.style.display = '';
                cancelBtn.className      = 'flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-medium text-sm hover:bg-slate-50 transition-colors';
            }

            _cb = opts.onConfirm || null;

            box.style.opacity   = '0';
            box.style.transform = 'scale(0.9)';
            modal.style.display = 'flex';

            requestAnimationFrame(function () {
                box.style.opacity   = '1';
                box.style.transform = 'scale(1)';
            });
        };

        function closeModal() {
            var modal = document.getElementById('app-modal');
            var box   = document.getElementById('app-modal-box');
            box.style.opacity   = '0';
            box.style.transform = 'scale(0.95)';
            setTimeout(function () { modal.style.display = 'none'; }, 120);
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('app-modal-confirm').addEventListener('click', function () {
                closeModal();
                if (typeof _cb === 'function') _cb();
            });
            document.getElementById('app-modal-cancel').addEventListener('click', closeModal);
            document.getElementById('app-modal-backdrop').addEventListener('click', closeModal);

            // معالجة النماذج التي لها data-confirm
            document.addEventListener('submit', function (e) {
                var form = e.target;
                var msg  = form.dataset.confirm;
                if (msg && !form._confirmed) {
                    e.preventDefault();
                    showConfirm({
                        title:       form.dataset.confirmTitle || 'تأكيد العملية',
                        message:     msg,
                        confirmText: form.dataset.confirmBtn   || 'تأكيد',
                        type:        form.dataset.confirmType  || 'danger',
                        onConfirm: function () {
                            form._confirmed = true;
                            form.submit();
                        },
                    });
                }
            });
        });
    })();
    </script>
</body>
</html>
