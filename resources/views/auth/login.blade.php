<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام الحضور والانصراف</title>
    <link rel="icon" type="image/png" href="{{ asset('icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex" style="background: linear-gradient(135deg, #1a3d57 0%, #31719d 40%, #317c77 70%, #265e5b 100%);">

    {{-- عناصر زخرفية --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full opacity-10" style="background: #e7c539;"></div>
        <div class="absolute -bottom-20 -left-20 w-72 h-72 rounded-full opacity-10" style="background: #4596cf;"></div>
        <div class="absolute top-1/2 left-1/3 w-48 h-48 rounded-full opacity-5" style="background: #4d9b97;"></div>
    </div>

    <div class="flex-1 flex items-center justify-center p-4 relative z-10">
        <div class="w-full max-w-md">

            {{-- الشعار والعنوان --}}
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center mb-4">
                    <img src="{{ asset('logo3.png') }}" alt="شعار النظام" class="h-20 w-auto object-contain"
                         onerror="this.style.display='none'; document.getElementById('fallback-logo').style.display='flex';">
                    <div id="fallback-logo" class="hidden w-16 h-16 rounded-2xl items-center justify-center shadow-xl"
                         style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-white mb-1">نظام الحضور والانصراف</h1>
                <p class="text-white/60 text-sm">سجّل دخولك للمتابعة</p>
            </div>

            {{-- بطاقة تسجيل الدخول --}}
            <div class="bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl p-8">

                @if (session('status'))
                    <div class="mb-5 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- البريد الإلكتروني --}}
                    <div>
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input id="email" name="email" type="email"
                                   value="{{ old('email') }}"
                                   required autofocus autocomplete="username"
                                   placeholder="admin@example.com"
                                   class="form-input pr-10 @error('email') border-red-400 focus:ring-red-300 @enderror">
                        </div>
                        @error('email')
                            <p class="form-error">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- كلمة المرور --}}
                    <div>
                        <label for="password" class="form-label">كلمة المرور</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input id="password" name="password" type="password"
                                   required autocomplete="current-password"
                                   placeholder="••••••••"
                                   class="form-input pr-10 @error('password') border-red-400 focus:ring-red-300 @enderror">
                        </div>
                        @error('password')
                            <p class="form-error">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- تذكرني --}}
                    <div class="flex items-center">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="remember" id="remember_me"
                                   class="rounded-md border-slate-300 text-secondary-500 focus:ring-secondary-300 w-4 h-4">
                            <span class="text-sm text-slate-600">تذكرني</span>
                        </label>
                    </div>

                    {{-- زر الدخول --}}
                    <button type="submit"
                            class="w-full text-white font-bold py-3 px-4 rounded-2xl transition-all duration-200
                                   focus:ring-4 focus:ring-secondary-300 shadow-lg hover:shadow-xl
                                   active:scale-[0.98] text-sm"
                            style="background: linear-gradient(135deg, #4596cf, #31719d);">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            تسجيل الدخول
                        </span>
                    </button>
                </form>
            </div>

            {{-- تذييل الصفحة --}}
            <p class="text-center text-xs text-white/40 mt-6">
                نظام الحضور والانصراف &copy; {{ date('Y') }}
            </p>
        </div>
    </div>

</body>
</html>
