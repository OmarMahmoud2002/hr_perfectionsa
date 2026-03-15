@extends('layouts.app')

@section('title', 'الموظفين')
@section('page-title', 'إدارة الموظفين')
@section('page-subtitle', 'عرض وإدارة بيانات الموظفين')

@section('content')

{{-- Header Row --}}
<div class="section-header">
    <div>
        <h1 class="section-title">الموظفين</h1>
        <p class="section-subtitle">{{ $employees->total() }} موظف في النظام</p>
    </div>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('employees.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        إضافة موظف
    </a>
    @endif
</div>

{{-- Search & Filter --}}
<div class="card mb-5">
    <div class="card-body">
        <form action="{{ route('employees.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="ابحث بالاسم أو رقم الموظف..."
                       class="form-input pr-10">
            </div>
            <select name="status" class="form-input sm:w-44">
                <option value="">جميع الحالات</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>نشط فقط</option>
                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>معطّل فقط</option>
            </select>
            <button type="submit" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
                فلترة
            </button>
            @if(!empty($filters['search']) || !empty($filters['status']))
            <a href="{{ route('employees.index') }}" class="btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                مسح
            </a>
            @endif
        </form>
    </div>
</div>

{{-- Employees Table --}}
@if($employees->count() > 0)
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>رقم البصمة (AC-No)</th>
                    <th>المرتب الأساسي</th>
                    <th>الحالة</th>
                    <th class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                <tr>
                    {{-- الموظف --}}
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-700 text-sm font-bold flex-shrink-0"
                                 style="background: #fff; border: 1.5px solid #e2e8f0;">
                                {{ mb_substr($employee->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">{{ $employee->name }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- AC-No --}}
                    <td>
                        <span class="font-mono text-sm bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg">
                            {{ $employee->ac_no }}
                        </span>
                    </td>

                    {{-- المرتب --}}
                    <td class="font-semibold text-slate-800">
                        {{ number_format($employee->basic_salary, 0) }}
                        <span class="text-xs text-slate-400 font-normal">ج.م</span>
                    </td>

                    {{-- الحالة --}}
                    <td>
                        @if($employee->is_active)
                            <span class="badge-success">نشط</span>
                        @else
                            <span class="badge-danger">معطّل</span>
                        @endif
                    </td>

                    {{-- الإجراءات --}}
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            {{-- عرض --}}
                            <a href="{{ route('employees.show', $employee) }}"
                               class="p-1.5 rounded-lg text-slate-500 hover:text-secondary-600 hover:bg-secondary-50 transition"
                               title="عرض التفاصيل">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>

                            @if(auth()->user()->isAdmin())
                            {{-- تعديل --}}
                            <a href="{{ route('employees.edit', $employee) }}"
                               class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition"
                               title="تعديل">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>

                            {{-- تعطيل --}}
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

    {{-- Pagination --}}
    @if($employees->hasPages())
    <div class="px-6 py-4 border-t border-slate-100">
        {{ $employees->links() }}
    </div>
    @endif
</div>

@else
{{-- Empty State --}}
<div class="card p-16 text-center">
    <div class="w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-4"
         style="background: rgba(69, 150, 207, 0.1);">
        <svg class="w-10 h-10" style="color: #4596cf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
    </div>
    <h3 class="text-lg font-bold text-slate-700 mb-2">لا يوجد موظفون</h3>
    <p class="text-slate-500 text-sm mb-6">
        @if(!empty($filters['search']))
            لم يُعثر على نتائج لـ "{{ $filters['search'] }}"
        @else
            لم يتم إضافة أي موظف بعد
        @endif
    </p>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('employees.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        إضافة أول موظف
    </a>
    @endif
</div>
@endif

@endsection
