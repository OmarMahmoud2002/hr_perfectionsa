@extends('layouts.app')

@section('title', 'الوظائف')
@section('page-title', 'إدارة الوظائف')
@section('page-subtitle', 'عرض وتحديث الوظائف وربطها بالموظفين')

@section('content')
@php
    $roleLabels = [
        'employee' => 'موظف',
        'user' => 'مراجع',
        'office_girl' => 'عامل مكتبي',
        'hr' => 'الموارد البشرية',
        'manager' => 'مدير',
        'admin' => 'مسؤول نظام',
    ];
@endphp
<div class="section-header">
    <div>
        <h1 class="section-title">الوظائف</h1>
        <p class="section-subtitle">{{ $jobTitles->count() }} وظيفة</p>
    </div>
    <a href="{{ route('job-titles.create') }}" class="btn-primary">إضافة وظيفة</a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>الوظيفة</th>
                    <th>عدد الموظفين</th>
                    <th>الحالة</th>
                    <th class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobTitles as $jobTitle)
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-800">{{ $jobTitle->name_ar }}</p>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ $jobTitle->system_role_mapping ? ('ربط الدور: ' . ($roleLabels[$jobTitle->system_role_mapping] ?? $jobTitle->system_role_mapping)) : 'ربط الدور: بدون ربط' }}
                            </p>
                        </td>
                        <td><span class="badge-gray">{{ $jobTitle->employees_count }}</span></td>
                        <td>
                            @if($jobTitle->is_active)
                                <span class="badge-success">نشطة</span>
                            @else
                                <span class="badge-danger">غير نشطة</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('job-titles.edit', $jobTitle) }}" class="btn-ghost btn-sm">تعديل</a>
                                @if($jobTitle->is_system)
                                    <button type="button" class="btn-ghost btn-sm opacity-60 cursor-not-allowed" disabled>حذف</button>
                                @else
                                    <form action="{{ route('job-titles.destroy', $jobTitle) }}" method="POST"
                                          data-confirm="{{ $jobTitle->employees_count > 0 ? 'هذه الوظيفة مرتبطة بـ '.$jobTitle->employees_count.' موظف. عند المتابعة سيتم فك الارتباط ثم الحذف. هل تريد المتابعة؟' : 'هل تريد حذف هذه الوظيفة؟' }}"
                                          data-confirm-title="تأكيد حذف الوظيفة"
                                          data-confirm-btn="حذف"
                                          data-confirm-type="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger btn-sm">حذف</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-10 text-slate-500">لا توجد وظائف مضافة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
