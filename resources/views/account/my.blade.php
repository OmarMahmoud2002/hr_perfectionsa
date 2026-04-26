@extends('layouts.app')

@section('title', 'حسابي')
@section('page-title', 'حسابي')
@section('page-subtitle', 'إدارة بياناتك الشخصية وإحصائياتك')

@section('content')
@php
    $periodLabel = \Carbon\Carbon::parse($periodStart)->locale('ar')->isoFormat('D MMM')
                 . ' - '
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

<div x-data="{
        tab: 'profile',
        editingName: @js($errors->has('name')),
        editingEmail: @js($errors->has('email')),
    }" class="space-y-5">
    <div class="card overflow-hidden p-5 relative">
        <div class="absolute -left-10 -top-10 h-44 w-44 rounded-full opacity-20" style="background: radial-gradient(circle, #4596cf, transparent 70%);"></div>
        <div class="absolute -bottom-12 -right-12 h-40 w-40 rounded-full opacity-20" style="background: radial-gradient(circle, #4d9b97, transparent 70%);"></div>

        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex h-20 w-20 flex-shrink-0 items-center justify-center overflow-hidden rounded-3xl border-4 border-white shadow-lg"
                 style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center text-3xl font-black text-white">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <h2 class="truncate text-xl font-black text-slate-800 sm:text-2xl">{{ $user->name }}</h2>
                <p class="mt-1 truncate text-sm text-slate-500">{{ $user->email ?: 'لا يوجد بريد مسجل بعد' }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="badge-blue">{{ $jobLabel }}</span>
                    <span class="badge-gray">{{ strtoupper($user->role) }}</span>
                </div>
            </div>

            <div class="w-full sm:w-auto">
                <div class="inline-flex w-full gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1 sm:w-auto">
                    <button type="button" @click="tab='profile'"
                            :class="tab === 'profile' ? 'bg-white text-slate-800 shadow' : 'text-slate-500'"
                            class="w-full rounded-lg px-3 py-2 text-xs font-semibold transition sm:w-auto">البيانات الأساسية</button>
                    <button type="button" @click="tab='security'"
                            :class="tab === 'security' ? 'bg-white text-slate-800 shadow' : 'text-slate-500'"
                            class="w-full rounded-lg px-3 py-2 text-xs font-semibold transition sm:w-auto">الأمان</button>
                    <button type="button" @click="tab='stats'"
                            :class="tab === 'stats' ? 'bg-white text-slate-800 shadow' : 'text-slate-500'"
                            class="w-full rounded-lg px-3 py-2 text-xs font-semibold transition sm:w-auto">إحصائياتي</button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="tab === 'profile'" x-transition class="card p-6">
        <div class="mb-5 flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-700">بيانات الحساب</h3>
                <p class="mt-1 text-xs text-slate-500">يمكنك تعديل الاسم والبريد المستخدم لتسجيل الدخول من هنا.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('account.my.update') }}" enctype="multipart/form-data" class="space-y-5"
              data-loading="true" data-loading-target="#profile-save-btn" data-loading-text="جاري الحفظ...">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="form-group mb-0">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="name" class="form-label !mb-0">الاسم</label>
                        <button type="button"
                                @click="editingName = true; $nextTick(() => $refs.nameField.focus())"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                title="تعديل الاسم">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.232-6.232a2.5 2.5 0 113.536 3.536L12.536 16.5 9 17l.5-3.5z"/>
                            </svg>
                        </button>
                    </div>
                    <input id="name" name="name" type="text"
                           x-ref="nameField"
                           :readonly="!editingName"
                           value="{{ old('name', $user->name) }}"
                           class="form-input transition @error('name') border-red-400 @enderror"
                           :class="editingName ? 'border-sky-300 bg-white ring-4 ring-sky-100/70' : 'cursor-default border-slate-200 bg-slate-50 text-slate-700'">
                    <p class="form-hint" x-show="!editingName">اضغط على القلم إذا أردت تعديل الاسم.</p>
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group mb-0">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="email" class="form-label !mb-0">البريد الإلكتروني</label>
                        <button type="button"
                                @click="editingEmail = true; $nextTick(() => $refs.emailField.focus())"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                title="تعديل البريد الإلكتروني">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.232-6.232a2.5 2.5 0 113.536 3.536L12.536 16.5 9 17l.5-3.5z"/>
                            </svg>
                        </button>
                    </div>
                    <input id="email" name="email" type="email"
                           x-ref="emailField"
                           :readonly="!editingEmail"
                           value="{{ old('email', $user->email) }}"
                           class="form-input ltr-input transition @error('email') border-red-400 @enderror"
                           :class="editingEmail ? 'border-sky-300 bg-white ring-4 ring-sky-100/70' : 'cursor-default border-slate-200 bg-slate-50 text-slate-700' "
                           placeholder="name@example.com">
                    <p class="form-hint" x-show="!editingEmail">اضغط على القلم لتغيير البريد المعتمد لتسجيل الدخول والإشعارات.</p>
                    <p class="form-hint" x-show="editingEmail">عند حفظ بريد جديد سيتم إرسال رسالة ترحيب إليه تلقائيًا.</p>
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
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

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="form-group mb-0">
                    <label for="avatar" class="form-label">الصورة الشخصية</label>
                    <input id="avatar" name="avatar" type="file" accept="image/*" class="form-input @error('avatar') border-red-400 @enderror">
                    <p class="mt-1 text-xs text-slate-400">JPG / PNG / WEBP - حتى 2MB</p>
                    @error('avatar')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group mb-0">
                    <label for="bio" class="form-label">نبذة قصيرة</label>
                    <textarea id="bio" name="bio" rows="4" class="form-input @error('bio') border-red-400 @enderror"
                              placeholder="اكتب نبذة بسيطة عنك...">{{ old('bio', $user->profile?->bio) }}</textarea>
                    @error('bio')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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

            <div class="border-t border-slate-100 pt-2">
                <button id="profile-save-btn" type="submit" class="btn-primary btn-lg">
                    حفظ التعديلات
                </button>
            </div>
        </form>
    </div>

    <div x-show="tab === 'security'" x-transition class="card p-6">
        <h3 class="mb-4 text-sm font-bold text-slate-700">الأمان وتغيير كلمة المرور</h3>

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

            <div class="flex items-center gap-3 border-t border-slate-100 pt-2">
                <button id="password-save-btn" type="submit" class="btn-gold btn-lg">تحديث كلمة المرور</button>
                @if (session('status') === 'password-updated')
                    <span class="text-xs font-semibold text-emerald-600">تم تحديث كلمة المرور.</span>
                @endif
            </div>
        </form>
    </div>

    <div x-show="tab === 'stats'" x-transition class="space-y-5">
        @if($user->employee)
            <div class="card overflow-hidden p-5 relative">
                <div class="absolute inset-0 opacity-80"
                     style="background: radial-gradient(circle at 85% 20%, rgba(231,197,57,.18), transparent 45%), linear-gradient(140deg, #2f6e98 0%, #2f7a76 100%);"></div>

                <div class="relative text-white">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-bold">الإنجازات</h3>
                        <span class="rounded-lg border border-white/30 bg-white/20 px-2.5 py-1 text-xs">Employee Of The Month</span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                        <div class="rounded-2xl border border-white/20 bg-white/12 p-4">
                            <p class="text-xs text-white/80">عدد مرات الفوز</p>
                            <p class="mt-1 text-3xl font-black">{{ (int) $employeeOfMonthWinsCount }}</p>
                            <p class="mt-1 text-xs text-white/75">مرة</p>
                        </div>

                        <div class="rounded-2xl border border-white/20 bg-white/12 p-4 lg:col-span-2">
                            <p class="mb-2 text-xs text-white/80">أشهر الفوز</p>
                            @if(($employeeOfMonthWinMonths ?? collect())->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach($employeeOfMonthWinMonths as $winMonth)
                                        <span class="rounded-full border border-white/25 bg-white/15 px-3 py-1 text-xs font-semibold">{{ $winMonth }}</span>
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
            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-700">إحصائياتي للشهر</h3>
                    <p class="mt-1 text-xs text-slate-400">{{ $periodLabel }}</p>
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
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                    <div class="rounded-xl bg-emerald-50 p-3 text-center">
                        <p class="text-xl font-black text-emerald-600">{{ $stats['present'] }}</p>
                        <p class="text-xs text-emerald-700">حضور</p>
                    </div>
                    <div class="rounded-xl bg-red-50 p-3 text-center">
                        <p class="text-xl font-black text-red-500">{{ $stats['absent'] }}</p>
                        <p class="text-xs text-red-600">غياب</p>
                    </div>
                    <div class="rounded-xl bg-amber-50 p-3 text-center">
                        <p class="text-lg font-black text-amber-600">{{ floor($stats['late_minutes'] / 60) }}:{{ str_pad($stats['late_minutes'] % 60, 2, '0', STR_PAD_LEFT) }}</p>
                        <p class="text-xs text-amber-700">تأخير</p>
                    </div>
                    <div class="rounded-xl p-3 text-center" style="background: rgba(49,113,157,0.08);">
                        <p class="text-lg font-black" style="color: #31719d;">{{ floor($stats['overtime_minutes'] / 60) }}:{{ str_pad($stats['overtime_minutes'] % 60, 2, '0', STR_PAD_LEFT) }}</p>
                        <p class="text-xs" style="color: #31719d;">Overtime</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 p-3 text-center">
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
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($record && $record->clock_out)
                                            <span class="font-mono text-xs font-semibold text-slate-700">{{ substr($record->clock_out, 0, 5) }}</span>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($record && $record->late_minutes > 0)
                                            <span class="text-xs font-semibold text-amber-600">{{ floor($record->late_minutes / 60) }}:{{ str_pad($record->late_minutes % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                        @else
                                            <span class="text-xs text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($record && $record->overtime_minutes > 0)
                                            <span class="text-xs font-semibold" style="color:#31719d;">{{ floor($record->overtime_minutes / 60) }}:{{ str_pad($record->overtime_minutes % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                        @else
                                            <span class="text-xs text-slate-300">-</span>
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
