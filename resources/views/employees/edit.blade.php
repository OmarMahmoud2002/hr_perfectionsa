@extends('layouts.app')

@section('title', 'تعديل بيانات: ' . $employee->name)
@section('page-title', 'تعديل بيانات الموظف')
@section('page-subtitle', $employee->name)

@section('content')

@php
    $avatarUrl = $employee->user?->profile?->avatar_path
        ? route('media.avatar', ['path' => $employee->user->profile->avatar_path])
        : null;
@endphp

{{-- Breadcrumb --}}
<nav class="breadcrumb">
    <a href="{{ route('employees.index') }}">الموظفين</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    <a href="{{ route('employees.show', $employee) }}">{{ $employee->name }}</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    <span class="text-slate-700 font-medium">تعديل</span>
</nav>

<div class="max-w-2xl mx-auto">
    <div class="card overflow-hidden">

        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-slate-100"
             style="background: linear-gradient(135deg, rgba(231,197,57,0.06), rgba(69,150,207,0.06));">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl overflow-hidden flex items-center justify-center text-white text-lg font-black"
                     style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $employee->name }}" class="w-full h-full object-cover">
                    @else
                        {{ mb_substr($employee->name, 0, 1) }}
                    @endif
                </div>
                <div>
                    <h2 class="font-bold text-slate-800">{{ $employee->name }}</h2>
                    <p class="text-xs text-slate-500">AC-No: {{ $employee->ac_no }}</p>
                </div>
            </div>

            @if($employee->user)
            <div class="mt-3 pt-3 border-t border-slate-200/70">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="badge-gray">{{ $employee->job_title?->label() ?? 'غير محدد' }}</span>
                    <span class="text-slate-500">الحساب:</span>
                    <span class="font-mono text-slate-700">{{ $employee->user->email }}</span>
                    <span class="{{ $employee->user->must_change_password ? 'badge-warning' : 'badge-success' }}">
                        {{ $employee->user->must_change_password ? 'بانتظار أول تغيير' : 'نشط' }}
                    </span>
                </div>
            </div>
            @endif
        </div>

        {{-- Form --}}
          <form action="{{ route('employees.update', $employee) }}" method="POST" class="p-6 space-y-5"
              data-loading="true" data-loading-target="#edit-submit" data-loading-text="جاري الحفظ...">
            @csrf
            @method('PUT')

            {{-- رقم الموظف --}}
            <div class="form-group">
                <label for="ac_no" class="form-label">
                    رقم الموظف في جهاز البصمة (AC-No)
                    <span class="text-red-500">*</span>
                </label>
                <input type="text" id="ac_no" name="ac_no"
                       value="{{ old('ac_no', $employee->ac_no) }}"
                       class="form-input @error('ac_no') border-red-400 focus:ring-red-300 @enderror">
                @error('ac_no')
                    <p class="form-error">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- الاسم --}}
            <div class="form-group">
                <label for="name" class="form-label">
                    الاسم الكامل
                    <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name"
                       value="{{ old('name', $employee->name) }}"
                       class="form-input @error('name') border-red-400 focus:ring-red-300 @enderror">
                @error('name')
                    <p class="form-error">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- الوظيفة --}}
            <div class="form-group">
                <label for="job_title" class="form-label">
                    الوظيفة
                    <span class="text-red-500">*</span>
                </label>
                <select id="job_title" name="job_title" class="form-input @error('job_title') border-red-400 focus:ring-red-300 @enderror">
                    <option value="">اختر الوظيفة</option>
                    @foreach(\App\Enums\JobTitle::cases() as $job)
                        <option value="{{ $job->value }}"
                            {{ old('job_title', $employee->job_title?->value) === $job->value ? 'selected' : '' }}>
                            {{ $job->label() }}
                        </option>
                    @endforeach
                </select>
                @error('job_title')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <hr class="border-slate-100">

            {{-- المرتب الأساسي --}}
            <div class="form-group">
                <label for="basic_salary" class="form-label">
                    المرتب الأساسي
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="basic_salary" name="basic_salary"
                           value="{{ old('basic_salary', $employee->basic_salary) }}"
                           min="0" step="0.01"
                           class="form-input pl-16 @error('basic_salary') border-red-400 focus:ring-red-300 @enderror">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-sm font-semibold text-slate-400 border-r border-slate-200 pr-3">ج.م</span>
                    </div>
                </div>
                @error('basic_salary')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- شيفت العمل --}}
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <div class="h-px flex-1 bg-slate-100"></div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">شيفت العمل</span>
                    <div class="h-px flex-1 bg-slate-100"></div>
                </div>
                <p class="text-xs text-slate-400">اتركها فارغة لاستخدام الإعداد الافتراضي للنظام (حضور 09:00 — انصراف 17:00)</p>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group mb-0">
                        <label for="work_start_time" class="form-label">وقت الحضور <span class="text-xs text-slate-400 font-normal">(افتراضي: 09:00)</span></label>
                        <input type="time" id="work_start_time" name="work_start_time"
                               value="{{ old('work_start_time', $employee->work_start_time) }}"
                               class="form-input @error('work_start_time') border-red-400 @enderror">
                        @error('work_start_time')<p class="form-error text-xs">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="work_end_time" class="form-label">وقت الانصراف <span class="text-xs text-slate-400 font-normal">(افتراضي: 17:00)</span></label>
                        <input type="time" id="work_end_time" name="work_end_time"
                               value="{{ old('work_end_time', $employee->work_end_time) }}"
                               class="form-input @error('work_end_time') border-red-400 @enderror">
                        @error('work_end_time')<p class="form-error text-xs">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="overtime_start_time" class="form-label">بدء الأوفرتايم <span class="text-xs text-slate-400 font-normal">(افتراضي: 17:30)</span></label>
                        <input type="time" id="overtime_start_time" name="overtime_start_time"
                               value="{{ old('overtime_start_time', $employee->overtime_start_time) }}"
                               class="form-input @error('overtime_start_time') border-red-400 @enderror">
                        @error('overtime_start_time')<p class="form-error text-xs">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="late_grace_minutes" class="form-label">فترة السماح بالتأخير <span class="text-xs text-slate-400 font-normal">(افتراضي: 30 د)</span></label>
                        <div class="relative">
                            <input type="number" id="late_grace_minutes" name="late_grace_minutes"
                                   value="{{ old('late_grace_minutes', $employee->late_grace_minutes) }}"
                                   min="0" max="240" step="5" placeholder="30"
                                   class="form-input pl-10 @error('late_grace_minutes') border-red-400 @enderror">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">د</span>
                        </div>
                        @error('late_grace_minutes')<p class="form-error text-xs">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                <button type="submit" id="edit-submit" class="btn-gold btn-lg flex-1 justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    حفظ التعديلات
                </button>
                <a href="{{ route('employees.show', $employee) }}" class="btn-ghost btn-lg">
                    إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
