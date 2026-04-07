{{-- القائمة الجانبية --}}
<aside id="sidebar"
       class="w-64 flex flex-col flex-shrink-0 fixed lg:relative inset-y-0 right-0 z-20 overflow-hidden"
       style="background: linear-gradient(180deg, #31719d 0%, #2a6a6a 50%, #317c77 100%); transition: transform 0.3s ease, width 0.3s ease; transform: translateX(100%)">

    {{-- Logo --}}
    <div class="flex items-center gap-3 px-5 py-4 border-b border-white/10">
        <div class="flex-shrink-0 w-10 h-10 rounded-xl overflow-hidden bg-white/10 flex items-center justify-center" style="background-color: white;">
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
            $isAdminLike = auth()->user()->isAdminLike();
        @endphp

        @if(!auth()->user()->isEvaluatorUser())
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
        @endif

        {{-- My Account --}}
        <a href="{{ route('account.my') }}"
           class="{{ $linkClass }} {{ request()->routeIs('account.my*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5.121 17.804A14.94 14.94 0 0112 16c2.5 0 4.847.61 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>حسابي</span>
            @if(request()->routeIs('account.my*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>

        @if(auth()->user()->isEmployee())
        <a href="{{ route('employee-of-month.vote.page') }}"
           class="{{ $linkClass }} {{ request()->routeIs('employee-of-month.vote.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.062 3.265a1 1 0 00.95.69h3.433c.969 0 1.371 1.24.588 1.81l-2.777 2.018a1 1 0 00-.363 1.118l1.062 3.266c.3.92-.755 1.688-1.538 1.118l-2.777-2.018a1 1 0 00-1.176 0l-2.777 2.018c-.783.57-1.838-.197-1.539-1.118l1.063-3.266a1 1 0 00-.364-1.118L2.98 8.692c-.783-.57-.38-1.81.588-1.81H7a1 1 0 00.951-.69l1.062-3.265z"/>
            </svg>
            <span>موظف الشهر</span>
            @if(request()->routeIs('employee-of-month.vote.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>
        @endif

        @if(auth()->user()->isEmployee())
        <a href="{{ route('attendance.remote.page') }}"
           class="{{ $linkClass }} {{ request()->routeIs('attendance.remote.page') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zm7-9h.01M12 15h.01M9 15h.01M15 15h.01"/>
            </svg>
            <span>تسجيل الحضور والانصراف online</span>
            @if(request()->routeIs('attendance.remote.page'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>
        @endif

        @if(auth()->user()->isEmployee())
        <a href="{{ route('tasks.my.index') }}"
           class="{{ $linkClass }} {{ request()->routeIs('tasks.my.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V9l-4-4H9zM9 5v4h4"/>
            </svg>
            <span>مهامي</span>
            @if(request()->routeIs('tasks.my.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>
        @endif

        @if(auth()->user()->isEmployee())
        <a href="{{ route('daily-performance.employee.index') }}"
           class="{{ $linkClass }} {{ request()->routeIs('daily-performance.employee.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/>
            </svg>
            <span>الأداء اليومي</span>
            @if(request()->routeIs('daily-performance.employee.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>
        @endif

        @if(auth()->user()->isEvaluatorUser())
        <a href="{{ route('tasks.evaluator.index') }}"
           class="{{ $linkClass }} {{ request()->routeIs('tasks.evaluator.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5h7m-7 4h7m-7 4h7m-9 4h9M5 5h.01M5 9h.01M5 13h.01M5 17h.01"/>
            </svg>
            <span>تقييم المهام</span>
            @if(request()->routeIs('tasks.evaluator.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>

        <a href="{{ route('daily-performance.review.index') }}"
           class="{{ $linkClass }} {{ request()->routeIs('daily-performance.review.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5h7m-7 4h7m-7 4h7m-9 4h9M5 5h.01M5 9h.01M5 13h.01M5 17h.01"/>
            </svg>
            <span>تقييم الأداء اليومي</span>
            @if(request()->routeIs('daily-performance.review.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>
        @endif

        @if($isAdminLike)
        <a href="{{ route('employee-of-month.admin.index') }}"
           class="{{ $linkClass }} {{ request()->routeIs('employee-of-month.admin.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>لوحة موظف الشهر</span>
            @if(request()->routeIs('employee-of-month.admin.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>

        <a href="{{ route('employee-of-month.vote.page') }}"
           class="{{ $linkClass }} {{ request()->routeIs('employee-of-month.vote.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.062 3.265a1 1 0 00.95.69h3.433c.969 0 1.371 1.24.588 1.81l-2.777 2.018a1 1 0 00-.363 1.118l1.062 3.266c.3.92-.755 1.688-1.538 1.118l-2.777-2.018a1 1 0 00-1.176 0l-2.777 2.018c-.783.57-1.838-.197-1.539-1.118l1.063-3.266a1 1 0 00-.364-1.118L2.98 8.692c-.783-.57-.38-1.81.588-1.81H7a1 1 0 00.951-.69l1.062-3.265z"/>
            </svg>
            <span>اختيار أفضل موظف</span>
            @if(request()->routeIs('employee-of-month.vote.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>

        <a href="{{ route('tasks.admin.index') }}"
           class="{{ $linkClass }} {{ request()->routeIs('tasks.admin.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V9l-4-4H9zM9 5v4h4"/>
            </svg>
            <span>إدارة المهام</span>
            @if(request()->routeIs('tasks.admin.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>

        <a href="{{ route('daily-performance.review.index') }}"
           class="{{ $linkClass }} {{ request()->routeIs('daily-performance.review.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5h7m-7 4h7m-7 4h7m-9 4h9M5 5h.01M5 9h.01M5 13h.01M5 17h.01"/>
            </svg>
            <span>تقييم الأداء اليومي</span>
            @if(request()->routeIs('daily-performance.review.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>
        @endif

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

        @if($isAdminLike)
        <a href="{{ route('locations.index') }}"
           class="{{ $linkClass }} {{ request()->routeIs('locations.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.243-4.243a8 8 0 1111.313 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>المواقع</span>
            @if(request()->routeIs('locations.*'))
                <span class="mr-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
            @endif
        </a>
        @endif

        {{-- Import --}}
        @if($isAdminLike)
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
        @if($isAdminLike)
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
