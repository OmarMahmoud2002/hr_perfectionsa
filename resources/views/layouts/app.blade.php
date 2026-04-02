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
                                <p class="text-xs text-slate-400">
                                    {{ $navUser->isAdminLike() ? 'إدارة النظام' : 'موظف' }}
                                </p>
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
        <div class="rounded-2xl shadow-2xl px-6 py-5 border border-white/20 text-white"
             style="background: radial-gradient(circle at 20% 20%, rgba(255,255,255,.14), transparent 45%), linear-gradient(135deg, #2e6d98 0%, #2f7c77 100%);">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <span class="absolute inset-0 rounded-full bg-white/20 animate-ping"></span>
                    <svg class="relative w-6 h-6 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-30"></circle>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-100"></path>
                    </svg>
                </div>
                <p id="global-loading-text" class="text-sm font-black tracking-wide">جاري التحميل</p>
            </div>
            <div class="mt-3 h-1.5 rounded-full bg-white/20 overflow-hidden" aria-hidden="true">
                <div class="h-full w-1/2 rounded-full bg-white/80 animate-pulse"></div>
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
