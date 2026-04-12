@extends('layouts.app')

@section('title', 'الموظفين')
@section('page-title', 'إدارة الموظفين')
@section('page-subtitle', 'عرض وإدارة بيانات الموظفين')

@section('content')
<div class="section-header">
    <div>
        <h1 class="section-title">الموظفين</h1>
        <p class="section-subtitle">{{ $employees->total() }} موظف في الدليل</p>
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

<div class="card mb-5">
    <div class="card-body">
        <form action="{{ route('employees.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
            <div class="relative w-full md:col-span-7 lg:col-span-8">
                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       oninput="clearTimeout(this._searchTimer); this._searchTimer = setTimeout(() => this.form.submit(), 400);"
                       placeholder="ابحث بالاسم أو رقم الموظف..."
                       class="form-input pr-10 !px-3.5 !h-9 !text-sm !w-full">
            </div>
            <div class="md:col-span-3 lg:col-span-2">
                <select name="status" onchange="this.form.submit()" class="form-input !w-full !min-w-0 !px-3.5 !h-9 !text-sm">
                    <option value="">جميع الحالات</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>نشط فقط</option>
                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>معطّل فقط</option>
                </select>
            </div>
            <div class="md:col-span-2 lg:col-span-2 flex md:justify-end">
                @if(!empty($filters['search']) || !empty($filters['status']))
                <a href="{{ route('employees.index') }}" class="btn-ghost btn-sm w-full md:w-auto justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    مسح
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

@if($employees->count() > 0)
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>الوظيفة</th>
                    <th>حساب الدخول</th>
                    <th>المرتب الأساسي</th>
                    <th>الحالة</th>
                    <th class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                <tr>
                    @php
                        $avatarUrl = $employee->user?->profile?->avatar_path
                            ? route('media.avatar', ['path' => $employee->user->profile->avatar_path])
                            : null;
                    @endphp
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl overflow-hidden flex items-center justify-center text-slate-700 text-sm font-bold flex-shrink-0"
                                 style="background: #fff; border: 1.5px solid #e2e8f0;">
                                @if($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ $employee->name }}" class="w-full h-full object-cover">
                                @else
                                    {{ mb_substr($employee->name, 0, 1) }}
                                @endif
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">{{ $employee->name }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $employee->position_line }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge-gray">{{ $employee->position_line }}</span>
                    </td>
                    <td>
                        @if($employee->user)
                            <div class="space-y-1">
                                <p class="text-xs font-medium text-slate-700">{{ $employee->user->email }}</p>
                                <span class="{{ $employee->user->must_change_password ? 'badge-warning' : 'badge-success' }}">
                                    {{ $employee->user->must_change_password ? 'لم يغيّر كلمة المرور' : 'نشط' }}
                                </span>
                            </div>
                        @else
                            <span class="badge-danger">بدون حساب</span>
                        @endif
                    </td>
                    <td class="font-semibold text-slate-800">
                        {{ number_format($employee->basic_salary, 0) }}
                        <span class="text-xs text-slate-400 font-normal">ج.م</span>
                    </td>
                    <td>
                        @if($employee->is_active)
                            <span class="badge-success">نشط</span>
                        @else
                            <span class="badge-danger">معطّل</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('employees.show', $employee) }}"
                               class="p-1.5 rounded-lg text-slate-500 hover:text-secondary-600 hover:bg-secondary-50 transition"
                               title="عرض التفاصيل">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>

                            @if(auth()->user()->isAdminLike())
                            <a href="{{ route('employees.edit', $employee) }}"
                               class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition"
                               title="تعديل">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>

                            <form action="{{ route('employees.destroy', $employee) }}" method="POST"
                                data-confirm="هل تريد تعطيل الموظف «{{ $employee->name }}»؟"
                                data-confirm-title="تأكيد التعطيل"
                                data-confirm-btn="تعطيل"
                                data-confirm-type="warning">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50 transition"
                                        title="تعطيل">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
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

    @if($employees->hasPages())
    <div class="px-6 py-4 border-t border-slate-100">
        {{ $employees->links() }}
    </div>
    @endif
</div>
@else
<div class="empty-state animate-fade-in">
    <div class="empty-state-icon animate-float-soft">
        <svg class="w-10 h-10" style="color: #4596cf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
    </div>
    <h3 class="text-lg font-bold text-slate-700 mb-2">لا يوجد موظفون</h3>
    <p class="text-slate-500 text-sm mb-1">لا توجد نتائج مطابقة للفلاتر الحالية.</p>
</div>
@endif
@endsection
