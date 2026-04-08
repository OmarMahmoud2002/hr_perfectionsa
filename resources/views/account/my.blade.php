@extends('layouts.app')

@section('title', 'حسابي')
@section('page-title', 'حسابي')
@section('page-subtitle', 'إدارة بياناتك الشخصية وإحصائياتك')

@section('content')
@php
    $periodLabel = \Carbon\Carbon::parse($periodStart)->locale('ar')->isoFormat('D MMM')
                 . ' — '
                 . \Carbon\Carbon::parse($periodEnd)->locale('ar')->isoFormat('D MMM YYYY');

    $avatarUrl = $user->profile?->avatar_path
        ? route('media.avatar', ['path' => $user->profile->avatar_path])
        : null;

    $jobLabel = $user->employee?->position_line ?? 'غير محدد';
    $employmentStartDate = optional($user->employee?->leaveProfile?->employment_start_date)?->format('Y-m-d') ?? 'غير محدد';
    $departmentName = $user->employee?->department?->name ?? 'غير محدد';
    $departmentManager = $user->employee?->department?->managerEmployee?->name ?? 'غير محدد';
@endphp

<nav class="breadcrumb">
    <a href="{{ route('dashboard') }}">لوحة التحكم</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    <span class="text-slate-700 font-medium">حسابي</span>
</nav>

<div x-data="{ tab: 'profile' }" class="space-y-5">
    <div class="card p-5 overflow-hidden relative">
        <div class="absolute -left-10 -top-10 w-44 h-44 rounded-full opacity-20" style="background: radial-gradient(circle, #4596cf, transparent 70%);"></div>
        <div class="absolute -right-12 -bottom-12 w-40 h-40 rounded-full opacity-20" style="background: radial-gradient(circle, #4d9b97, transparent 70%);"></div>

        <div class="relative flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <div class="w-20 h-20 rounded-3xl overflow-hidden border-4 border-white shadow-lg flex-shrink-0"
                 style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-white text-3xl font-black">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <h2 class="text-xl sm:text-2xl font-black text-slate-800 truncate">{{ $user->name }}</h2>
                <p class="text-sm text-slate-500 truncate mt-1">{{ $user->email }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="badge-blue">{{ $jobLabel }}</span>
                    <span class="badge-gray">{{ strtoupper($user->role) }}</span>
                </div>
            </div>

            <div class="w-full sm:w-auto">
                <div class="inline-flex p-1 rounded-xl bg-slate-100 border border-slate-200 gap-1 w-full sm:w-auto">
                    <button type="button" @click="tab='profile'"
                            :class="tab === 'profile' ? 'bg-white text-slate-800 shadow' : 'text-slate-500'"
                            class="px-3 py-2 rounded-lg text-xs font-semibold transition w-full sm:w-auto">البيانات الأساسية</button>
                    <button type="button" @click="tab='security'"
                            :class="tab === 'security' ? 'bg-white text-slate-800 shadow' : 'text-slate-500'"
                            class="px-3 py-2 rounded-lg text-xs font-semibold transition w-full sm:w-auto">الأمان</button>
                    <button type="button" @click="tab='stats'"
                            :class="tab === 'stats' ? 'bg-white text-slate-800 shadow' : 'text-slate-500'"
                            class="px-3 py-2 rounded-lg text-xs font-semibold transition w-full sm:w-auto">إحصائياتي</button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="tab === 'profile'" x-transition class="card p-6">
        <h3 class="text-sm font-bold text-slate-700 mb-4">البيانات الأساسية</h3>

        <form method="POST" action="{{ route('account.my.update') }}" enctype="multipart/form-data" class="space-y-5"
              data-loading="true" data-loading-target="#profile-save-btn" data-loading-text="جاري الحفظ...">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group mb-0">
                    <label class="form-label">الاسم</label>
                    <input type="text" class="form-input bg-slate-100" value="{{ $user->name }}" disabled>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="text" class="form-input bg-slate-100" value="{{ $user->email }}" disabled>
                </div>
                <div class="form-group mb-0 md:col-span-2">
                    <label class="form-label">الوظيفة</label>
                    <input type="text" class="form-input bg-slate-100" value="{{ $jobLabel }}" disabled>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">تاريخ بداية العمل</label>
                    <input type="text" class="form-input bg-slate-100" value="{{ $employmentStartDate }}" disabled>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">القسم</label>
                    <input type="text" class="form-input bg-slate-100" value="{{ $departmentName }}" disabled>
                </div>
                <div class="form-group mb-0 md:col-span-2">
                    <label class="form-label">مدير القسم</label>
                    <input type="text" class="form-input bg-slate-100" value="{{ $departmentManager }}" disabled>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group mb-0">
                    <label for="avatar" class="form-label">الصورة الشخصية</label>
                    <input id="avatar" name="avatar" type="file" accept="image/*" class="form-input @error('avatar') border-red-400 @enderror">
                    <p class="text-xs text-slate-400 mt-1">JPG/PNG/WEBP - حتى 2MB</p>
                    @error('avatar')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group mb-0">
                    <label for="bio" class="form-label">Bio</label>
                    <textarea id="bio" name="bio" rows="4" class="form-input @error('bio') border-red-400 @enderror"
                              placeholder="اكتب نبذة بسيطة عنك...">{{ old('bio', $user->profile?->bio) }}</textarea>
                    @error('bio')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group mb-0">
                    <label for="social_link_1" class="form-label">رابط تواصل 1</label>
                    <input id="social_link_1" name="social_link_1" type="url"
                           value="{{ old('social_link_1', $user->profile?->social_link_1) }}"
                           placeholder="https://linkedin.com/in/username"
                           class="form-input @error('social_link_1') border-red-400 @enderror">
                    @error('social_link_1')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group mb-0">
                    <label for="social_link_2" class="form-label">رابط تواصل 2</label>
                    <input id="social_link_2" name="social_link_2" type="url"
                           value="{{ old('social_link_2', $user->profile?->social_link_2) }}"
                           placeholder="https://facebook.com/username"
                           class="form-input @error('social_link_2') border-red-400 @enderror">
                    @error('social_link_2')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100">
                <button id="profile-save-btn" type="submit" class="btn-primary btn-lg">
                    حفظ التعديلات
                </button>
            </div>
        </form>
    </div>

    <div x-show="tab === 'security'" x-transition class="card p-6">
        <h3 class="text-sm font-bold text-slate-700 mb-4">الأمان وتغيير كلمة المرور</h3>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4"
              data-loading="true" data-loading-target="#password-save-btn" data-loading-text="جاري التحديث...">
            @csrf
            @method('PUT')

            <div class="form-group mb-0">
                <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                <input id="current_password" name="current_password" type="password" class="form-input">
                @if($errors->updatePassword->get('current_password'))
                    <p class="form-error">{{ $errors->updatePassword->first('current_password') }}</p>
                @endif
            </div>

            <div class="form-group mb-0">
                <label for="password" class="form-label">كلمة المرور الجديدة</label>
                <input id="password" name="password" type="password" class="form-input">
                @if($errors->updatePassword->get('password'))
                    <p class="form-error">{{ $errors->updatePassword->first('password') }}</p>
                @endif
            </div>

            <div class="form-group mb-0">
                <label for="password_confirmation" class="form-label">تأكيد كلمة المرور</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-input">
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center gap-3">
                <button id="password-save-btn" type="submit" class="btn-gold btn-lg">تحديث كلمة المرور</button>
                @if (session('status') === 'password-updated')
                    <span class="text-xs text-emerald-600 font-semibold">تم تحديث كلمة المرور.</span>
                @endif
            </div>
        </form>
    </div>

    <div x-show="tab === 'stats'" x-transition class="space-y-5">
        @if($user->employee)
            <div class="card p-5 overflow-hidden relative">
                <div class="absolute inset-0 opacity-80"
                     style="background: radial-gradient(circle at 85% 20%, rgba(231,197,57,.18), transparent 45%), linear-gradient(140deg, #2f6e98 0%, #2f7a76 100%);"></div>

                <div class="relative text-white">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <h3 class="text-sm font-bold">الإنجازات</h3>
                        <span class="text-xs bg-white/20 border border-white/30 rounded-lg px-2.5 py-1">Employee Of The Month</span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                        <div class="rounded-2xl bg-white/12 border border-white/20 p-4">
                            <p class="text-xs text-white/80">عدد مرات الفوز</p>
                            <p class="text-3xl font-black mt-1">{{ (int) $employeeOfMonthWinsCount }}</p>
                            <p class="text-xs text-white/75 mt-1">مرة</p>
                        </div>

                        <div class="rounded-2xl bg-white/12 border border-white/20 p-4 lg:col-span-2">
                            <p class="text-xs text-white/80 mb-2">أشهر الفوز</p>
                            @if(($employeeOfMonthWinMonths ?? collect())->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach($employeeOfMonthWinMonths as $winMonth)
                                        <span class="text-xs font-semibold rounded-full px-3 py-1 bg-white/15 border border-white/25">{{ $winMonth }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-white/80">لم يتم تحقيق لقب موظف الشهر حتى الآن.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-700">إحصائياتي للشهر</h3>
                    <p class="text-xs text-slate-400 mt-1">{{ $periodLabel }}</p>
                </div>
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <select name="month" onchange="this.form.submit()" class="form-input !w-auto !min-w-0 !px-4 !py-1.5 !text-xs">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->locale('ar')->isoFormat('MMMM') }}
                            </option>
                        @endforeach
                    </select>
                    <select name="year" onchange="this.form.submit()" class="form-input !w-auto !min-w-0 !px-4 !py-1.5 !text-xs">
                        @foreach(range(now()->year, now()->year - 2) as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if(!$user->employee)
                <div class="alert-info">
                    <p>حسابك غير مرتبط بملف موظف حتى الآن، برجاء التواصل مع الإدارة.</p>
                </div>
            @elseif(!$stats)
                <div class="alert-warning">
                    <p>لا توجد بيانات حضور متاحة لهذا الشهر.</p>
                </div>
            @else
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                    <div class="bg-emerald-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-emerald-600">{{ $stats['present'] }}</p>
                        <p class="text-xs text-emerald-700">حضور</p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-red-500">{{ $stats['absent'] }}</p>
                        <p class="text-xs text-red-600">غياب</p>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-black text-amber-600">{{ floor($stats['late_minutes'] / 60) }}:{{ str_pad($stats['late_minutes'] % 60, 2, '0', STR_PAD_LEFT) }}</p>
                        <p class="text-xs text-amber-700">تأخير</p>
                    </div>
                    <div class="rounded-xl p-3 text-center" style="background: rgba(49,113,157,0.08);">
                        <p class="text-lg font-black" style="color: #31719d;">{{ floor($stats['overtime_minutes'] / 60) }}:{{ str_pad($stats['overtime_minutes'] % 60, 2, '0', STR_PAD_LEFT) }}</p>
                        <p class="text-xs" style="color: #31719d;">Overtime</p>
                    </div>
                    <div class="bg-slate-100 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-slate-700">{{ $stats['working_days'] }}</p>
                        <p class="text-xs text-slate-500">أيام العمل</p>
                    </div>
                </div>
            @endif
        </div>

        @if($user->employee && $dailyBreakdown->isNotEmpty())
            <div class="card overflow-hidden">
                <div class="card-header">
                    <h3 class="text-sm font-bold text-slate-700">جدول الحضور</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>اليوم</th>
                                <th class="text-center">الحضور</th>
                                <th class="text-center">الانصراف</th>
                                <th class="text-center">التأخير</th>
                                <th class="text-center">Overtime</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailyBreakdown as $day)
                                @php $record = $day['record']; @endphp
                                <tr>
                                    <td class="font-mono text-xs text-slate-600">{{ $day['date']->format('Y-m-d') }}</td>
                                    <td class="text-sm text-slate-600">{{ $day['day_name'] }}</td>
                                    <td class="text-center">
                                        @if($record && $record->clock_in)
                                            <span class="font-mono text-xs font-semibold text-slate-700">{{ substr($record->clock_in, 0, 5) }}</span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($record && $record->clock_out)
                                            <span class="font-mono text-xs font-semibold text-slate-700">{{ substr($record->clock_out, 0, 5) }}</span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($record && $record->late_minutes > 0)
                                            <span class="text-xs font-semibold text-amber-600">{{ floor($record->late_minutes / 60) }}:{{ str_pad($record->late_minutes % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                        @else
                                            <span class="text-slate-300 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($record && $record->overtime_minutes > 0)
                                            <span class="text-xs font-semibold" style="color:#31719d;">{{ floor($record->overtime_minutes / 60) }}:{{ str_pad($record->overtime_minutes % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                        @else
                                            <span class="text-slate-300 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($day['status'])
                                            @case('present') <span class="badge-success">حاضر</span> @break
                                            @case('late') <span class="badge-warning">متأخر</span> @break
                                            @case('absent') <span class="badge-danger">غائب</span> @break
                                            @case('public_holiday') <span class="badge-info">إجازة رسمية</span> @break
                                            @case('friday') <span class="badge-gray">جمعة</span> @break
                                            @case('weekly_leave') <span class="badge" style="background:#ede9fe;color:#7c3aed;border:1px solid #ddd6fe;">إجازة أسبوعية</span> @break
                                        @endswitch
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
