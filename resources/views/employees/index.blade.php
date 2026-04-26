@extends('layouts.app')

@section('title', 'الموظفون')
@section('page-title', 'إدارة الموظفين')
@section('page-subtitle', 'متابعة جاهزية الحسابات والبريد الإلكتروني من مكان واحد')

@section('content')
@php
    $emailFilter = (string) ($filters['email_status'] ?? 'all');
    $searchFilter = trim((string) ($filters['search'] ?? ''));
    $perPageFilter = (int) ($filters['per_page'] ?? 20);
    $hasFilters = $searchFilter !== '' || $emailFilter !== 'all' || $perPageFilter !== 20;
    $showBottomPerPage = ($employees->total() > 20) || $perPageFilter !== 20;
    $summary = $directorySummary ?? [
        'total_employees' => 0,
        'ready_accounts' => 0,
        'missing_email' => 0,
        'without_account' => 0,
        'pending_login_setup' => 0,
    ];
@endphp

<div class="section-header">
    <div>
        <h1 class="section-title">الموظفون</h1>
        <p class="section-subtitle">عرض {{ $employees->firstItem() ?? 0 }} - {{ $employees->lastItem() ?? 0 }} من أصل {{ $employees->total() }} موظف</p>
    </div>

    @if(auth()->user()->isAdminLike())
        <a href="{{ route('employees.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            إضافة موظف
        </a>
    @endif
</div>

<section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
    <div class="px-5 py-5 sm:px-6"
        >
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <!-- <div class="max-w-2xl">
                <span class="inline-flex items-center rounded-full border border-sky-200 bg-white/90 px-3 py-1 text-[11px] font-black tracking-[0.18em] text-sky-700 shadow-sm">
                    Employee Directory
                </span>
                <h2 class="mt-3 text-xl font-black leading-tight text-slate-900 sm:text-2xl">لوحة متابعة جاهزية الدخول</h2>
                <p class="mt-2 max-w-xl text-sm leading-7 text-slate-600">واجهة أوضح لمراجعة الحسابات الجاهزة، واكتشاف الموظفين الذين يحتاجون بريدًا أو متابعة قبل بدء استخدام النظام.</p>
            </div> -->

            <div class="flex flex-wrap gap-2">
                @if((int) $summary['pending_login_setup'] > 0)
                    <a href="{{ route('employees.index', ['email_status' => 'missing', 'per_page' => $perPageFilter]) }}"
                       class="inline-flex items-center justify-center rounded-2xl px-4 py-3 text-sm font-black text-white shadow-lg shadow-sky-200/60 transition hover:-translate-y-0.5"
                       style="background: linear-gradient(135deg, #31719d 0%, #317c77 100%);">
                        متابعة الحسابات غير الجاهزة
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 border-t border-slate-100 bg-slate-50/80 p-4 xl:grid-cols-4 sm:p-5">
        <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-3.5 sm:p-4">
            <p class="text-[11px] font-bold text-slate-500 sm:text-xs">إجمالي الموظفين</p>
            <p class="mt-2 text-xl font-black text-slate-900 sm:text-2xl">{{ $summary['total_employees'] }}</p>
            <p class="mt-1 text-[11px] leading-5 text-slate-500 sm:text-xs">ضمن نطاق الصلاحيات الحالية.</p>
        </div>
        <div class="min-w-0 rounded-2xl border border-emerald-100 bg-emerald-50 p-3.5 sm:p-4">
            <p class="text-[11px] font-bold text-emerald-700 sm:text-xs">جاهزون للدخول</p>
            <p class="mt-2 text-xl font-black text-emerald-900 sm:text-2xl">{{ $summary['ready_accounts'] }}</p>
            <p class="mt-1 text-[11px] leading-5 text-emerald-700/80 sm:text-xs">لديهم حساب وبريد صالح.</p>
        </div>
        <div class="min-w-0 rounded-2xl border border-amber-100 bg-amber-50 p-3.5 sm:p-4">
            <p class="text-[11px] font-bold text-amber-700 sm:text-xs">ينتظرون بريدًا</p>
            <p class="mt-2 text-xl font-black text-amber-900 sm:text-2xl">{{ $summary['missing_email'] }}</p>
            <p class="mt-1 text-[11px] leading-5 text-amber-700/80 sm:text-xs">لن يتمكنوا من تسجيل الدخول بعد.</p>
        </div>
        <div class="min-w-0 rounded-2xl border border-rose-100 bg-rose-50 p-3.5 sm:p-4">
            <p class="text-[11px] font-bold text-rose-700 sm:text-xs">بحاجة إلى متابعة</p>
            <p class="mt-2 text-xl font-black text-rose-900 sm:text-2xl">{{ $summary['pending_login_setup'] }}</p>
            <p class="mt-1 text-[11px] leading-5 text-rose-700/80 sm:text-xs">بلا بريد أو بلا حساب مكتمل.</p>
        </div>
    </div>
</section>

<section class="card mt-5">
    <div class="card-body">
        <form action="{{ route('employees.index') }}" method="GET" class="grid grid-cols-2 gap-3 xl:grid-cols-12 xl:items-end">
            <input type="hidden" name="per_page" value="{{ $perPageFilter }}">

            <div class="relative col-span-2 xl:col-span-7">
                <label for="search" class="form-label">بحث سريع</label>
                <div class="pointer-events-none absolute inset-y-0 right-0 top-7 flex items-center pr-3.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input id="search" type="text" name="search" value="{{ $searchFilter }}"
                       oninput="clearTimeout(this._searchTimer); this._searchTimer = setTimeout(() => this.form.submit(), 350);"
                       placeholder="ابحث بالاسم أو رقم الموظف AC-No"
                       class="form-input !w-full !rounded-2xl !border-slate-200 !bg-slate-50/80 pr-10 shadow-sm">
            </div>

            <div class="col-span-1 xl:col-span-3">
                <label for="email_status" class="form-label">حالة الحساب</label>
                <select id="email_status" name="email_status" class="form-input !rounded-2xl !border-slate-200 !bg-slate-50/80 shadow-sm" onchange="this.form.submit()" style="padding-right: 35px;">
                    <option value="all" @selected($emailFilter === 'all')>الكل</option>
                    <option value="missing" @selected($emailFilter === 'missing')>يحتاج بريد</option>
                    <option value="ready" @selected($emailFilter === 'ready')>جاهز للدخول</option>
                    <option value="no_account" @selected($emailFilter === 'no_account')>بدون حساب</option>
                </select>
            </div>

            <div class="col-span-1 flex items-end gap-2 xl:col-span-2">
                @if($hasFilters)
                    <a href="{{ route('employees.index') }}" class="btn-ghost w-full justify-center !rounded-2xl !border !border-slate-200 !bg-slate-50 !text-slate-700 hover:!bg-slate-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        تصفير الفلاتر
                    </a>
                @else
                    <div class="flex min-h-[46px] w-full items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-3 text-center text-xs font-semibold text-slate-500">
                        الوضع الافتراضي مفعل
                    </div>
                @endif
            </div>
        </form>
    </div>
</section>

@if($employees->count() > 0)
    <div class="card mt-5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table table-fixed min-w-[1180px]">
                <thead>
                    <tr>
                        <th class="w-[25%]">الموظف</th>
                        <th class="w-[14%]">الوظيفة</th>
                        <th class="w-[28%]">حالة حساب الدخول</th>
                        <th class="w-[10%]">المرتب الأساسي</th>
                        <th class="w-[13%]">الحالة</th>
                        <th class="w-[10%] text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                        @php
                            $avatarUrl = $employee->user?->profile?->avatar_path
                                ? route('media.avatar', ['path' => $employee->user->profile->avatar_path])
                                : null;
                            $account = $employee->user;
                            $hasEmail = is_string($account?->email) && trim((string) $account->email) !== '';
                            $needsAttention = ! $account || ! $hasEmail;
                        @endphp
                        <tr class="{{ $needsAttention ? 'bg-amber-50/30' : '' }}">
                            <td class="align-top">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-12 w-12 min-h-[3rem] min-w-[3rem] flex-shrink-0 items-center justify-center overflow-hidden rounded-2xl ring-1 ring-slate-200"
                                         style="background: linear-gradient(135deg, rgba(69,150,207,.14), rgba(77,155,151,.14));">
                                        @if($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="{{ $employee->name }}" class="block h-full w-full object-cover object-center">
                                        @else
                                            <span class="text-sm font-black text-slate-700">{{ mb_substr($employee->name, 0, 1) }}</span>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-black text-slate-800">{{ $employee->name }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                            <span class="badge-gray">{{ $employee->position_line }}</span>
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 font-semibold text-slate-600">
                                                AC-No: {{ $employee->ac_no }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="align-top">
                                <span class="badge-gray">{{ $employee->position_line }}</span>
                            </td>

                            <td class="align-top">
                                @if($account)
                                    <div class="space-y-2">
                                        @if($hasEmail)
                                            <div>
                                                <p class="truncate text-xs font-semibold text-slate-800">{{ $account->email }}</p>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="badge-success">جاهز للدخول</span>
                                                <span class="{{ $account->must_change_password ? 'badge-warning' : 'badge-info' }}">
                                                    {{ $account->must_change_password ? 'بانتظار أول تغيير لكلمة المرور' : 'الحساب مفعل' }}
                                                </span>
                                            </div>
                                        @else
                                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="badge-warning">غير مسجل له بريد</span>
                                                    @if(auth()->user()->isAdminLike())
                                                        <a href="{{ route('employees.edit', $employee) }}" class="text-xs font-black text-amber-800 hover:text-amber-900">استكمال الآن</a>
                                                    @endif
                                                </div>
                                                <p class="mt-2 text-[11px] leading-5 text-amber-800/85">لن يتمكن الموظف من تسجيل الدخول أو استلام البريد حتى إضافة عنوان صالح.</p>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-3 py-3">
                                        <span class="badge-danger">بدون حساب</span>
                                        <p class="mt-2 text-[11px] leading-5 text-rose-800/85">الملف موجود لكن حساب الدخول لم يُنشأ أو يحتاج مراجعة.</p>
                                    </div>
                                @endif
                            </td>

                            <td class="align-top font-semibold text-slate-800">
                                {{ number_format($employee->basic_salary, 0) }}
                                <span class="text-xs font-normal text-slate-400">ج.م</span>
                            </td>

                            <td class="align-top">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if($employee->is_active)
                                        <span class="badge-success">نشط</span>
                                    @else
                                        <span class="badge-danger">معطّل</span>
                                    @endif

                                    @if($needsAttention)
                                        <span class="badge-warning">يحتاج متابعة</span>
                                    @endif
                                </div>
                            </td>

                            <td class="align-top">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('employees.show', $employee) }}"
                                       class="rounded-lg p-1.5 text-slate-500 transition hover:bg-secondary-50 hover:text-secondary-600"
                                       title="عرض التفاصيل">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    @if(auth()->user()->isAdminLike())
                                        <a href="{{ route('employees.edit', $employee) }}"
                                           class="rounded-lg p-1.5 text-slate-500 transition hover:bg-amber-50 hover:text-amber-600"
                                           title="تعديل">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>

                                        <form action="{{ route('employees.destroy', $employee) }}" method="POST"
                                              data-confirm="هل تريد حذف الموظف «{{ $employee->name }}» نهائيًا؟ لا يمكن التراجع."
                                              data-confirm-title="تأكيد الحذف النهائي"
                                              data-confirm-btn="حذف"
                                              data-confirm-type="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-lg p-1.5 text-slate-500 transition hover:bg-red-50 hover:text-red-600"
                                                    title="حذف">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-100 px-4 py-4 sm:px-6 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-slate-500">
                عرض {{ $employees->firstItem() ?? 0 }} - {{ $employees->lastItem() ?? 0 }} من {{ $employees->total() }} موظف
            </p>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                @if($showBottomPerPage)
                    <form action="{{ route('employees.index') }}" method="GET"
                          class="inline-flex items-center justify-between gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-600">
                        <input type="hidden" name="search" value="{{ $searchFilter }}">
                        <input type="hidden" name="email_status" value="{{ $emailFilter }}">
                        <label for="bottom_per_page" class="whitespace-nowrap font-semibold text-slate-500">لكل صفحة</label>
                        <select id="bottom_per_page" name="per_page"
                                style="padding-right: 35px;"
                                class="min-w-[68px] rounded-full border-0 bg-transparent py-0 pr-1 pl-5 text-xs font-black text-slate-700 focus:ring-0"
                                onchange="this.form.submit()">
                            <option value="10" @selected($perPageFilter === 10)>10</option>
                            <option value="20" @selected($perPageFilter === 20)>20</option>
                            <option value="50" @selected($perPageFilter === 50)>50</option>
                            <option value="100" @selected($perPageFilter === 100)>100</option>
                        </select>
                    </form>
                @endif

                @if($employees->hasPages())
                    {{ $employees->links() }}
                @endif
            </div>
        </div>
    </div>
@else
    <div class="empty-state mt-5 animate-fade-in">
        <div class="empty-state-icon animate-float-soft">
            <svg class="w-10 h-10" style="color: #4596cf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <h3 class="mb-2 text-lg font-bold text-slate-700">لا توجد نتائج مطابقة</h3>
        <p class="mb-4 text-sm text-slate-500">جرّب تعديل البحث أو إزالة الفلاتر الحالية لعرض مزيد من الموظفين.</p>
        @if($hasFilters)
            <a href="{{ route('employees.index') }}" class="btn-ghost">إزالة الفلاتر</a>
        @endif
    </div>
@endif
@endsection
