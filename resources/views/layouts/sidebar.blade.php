{{-- القائمة الجانبية --}}
<aside id="sidebar"
       class="w-64 flex flex-col flex-shrink-0 fixed lg:relative inset-y-0 right-0 z-20 overflow-hidden"
       style="background: linear-gradient(180deg, #31719d 0%, #2a6a6a 50%, #317c77 100%); transition: transform 0.3s ease, width 0.3s ease; transform: translateX(100%)">

    {{-- Logo --}}
    <div class="flex items-center gap-3 px-5 py-4 border-b border-white/10">
        <div class="flex-shrink-0 w-10 h-10 rounded-xl overflow-hidden bg-white/10 flex items-center justify-center">
            <img src="{{ asset('icon.png') }}" alt="أيقونة النظام" class="w-8 h-8 object-contain"
                 onerror="this.parentElement.innerHTML='<svg class=\'w-5 h-5 text-white\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z\'/></svg>'">
        </div>
        <div class="min-w-0 flex-1">
            <p class="font-bold text-sm text-white leading-tight truncate">نظام الحضور</p>
            <p class="text-xs text-white/60">والانصراف</p>
        </div>
        {{-- زر إغلاق القائمة --}}
        <button onclick="toggleSidebar()" class="flex-shrink-0 p-1.5 rounded-lg text-white/50 hover:text-white hover:bg-white/10 transition-all" title="إغلاق القائمة">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Logo Image (إن وجد) --}}
    {{-- يمكن إضافة شعار النظام هنا --}}

    {{-- Navigation --}}
    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto scrollbar-thin">

        @php
            $linkClass = "flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200";
            $activeClass = "bg-white/20 text-white shadow-inner";
            $inactiveClass = "text-white/70 hover:bg-white/10 hover:text-white";
        @endphp

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="{{ $linkClass }} {{ request()->routeIs('dashboard') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>لوحة التحكم</span>
            @if(request()->routeIs('dashboard'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>

        {{-- Divider --}}
        <div class="pt-2 pb-1">
            <p class="px-3 text-xs text-white/40 font-semibold uppercase tracking-wider">إدارة البيانات</p>
        </div>

        {{-- Employees --}}
        <a href="{{ route('employees.index') }}"
           class="{{ $linkClass }} {{ request()->routeIs('employees.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>الموظفين</span>
            @if(request()->routeIs('employees.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>

        {{-- Import --}}
        @if(auth()->user()->isAdmin())
        <a href="{{ route('import.form') }}"
           class="{{ $linkClass }} {{ request()->routeIs('import.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <span>رفع ملف Excel</span>
            @if(request()->routeIs('import.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>
        @endif

        {{-- Divider --}}
        <div class="pt-2 pb-1">
            <p class="px-3 text-xs text-white/40 font-semibold uppercase tracking-wider">التقارير</p>
        </div>

        {{-- Attendance --}}
        <a href="{{ route('attendance.index') }}"
           class="{{ $linkClass }} {{ request()->routeIs('attendance.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span>تقارير الحضور</span>
            @if(request()->routeIs('attendance.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>

        {{-- Payroll --}}
        <a href="{{ route('payroll.index') }}"
           class="{{ $linkClass }} {{ request()->routeIs('payroll.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>المرتبات</span>
            @if(request()->routeIs('payroll.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>

        {{-- Settings --}}
        @if(auth()->user()->isAdmin())
        <div class="pt-2 pb-1">
            <p class="px-3 text-xs text-white/40 font-semibold uppercase tracking-wider">النظام</p>
        </div>

        <a href="{{ route('settings.index') }}"
           class="{{ $linkClass }} {{ request()->routeIs('settings.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>الإعدادات</span>
            @if(request()->routeIs('settings.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>
        @endif

    </nav>

    {{-- Footer --}}
    <div class="px-5 py-3 border-t border-white/10">
        <div class="flex items-center gap-2">
            <img src="{{ asset('logo.png') }}" alt="شعار النظام" class="h-6 w-auto opacity-60 object-contain"
                 onerror="this.style.display='none'">
            <p class="text-xs text-white/40">v1.0 &copy; {{ date('Y') }}</p>
        </div>
    </div>

</aside>
