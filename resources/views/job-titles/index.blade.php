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
                    <tr x-data="{ showMembers: false }">
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
                                <button type="button" class="btn-ghost btn-sm "style="background: #2e7d32;color: #fff;" @click="showMembers = true">عرض</button>
                                <a href="{{ route('job-titles.edit', $jobTitle) }}" class="btn-ghost btn-sm" style="background-color: #007BFF;color: white;">تعديل</a>
                                <form action="{{ route('job-titles.destroy', $jobTitle) }}" method="POST"
                                      data-confirm="{{ $jobTitle->employees_count > 0 ? 'هذه الوظيفة مرتبطة بـ '.$jobTitle->employees_count.' موظف. عند المتابعة سيتم فك الارتباط ثم الحذف. هل تريد المتابعة؟' : 'هل تريد حذف هذه الوظيفة؟' }}"
                                      data-confirm-title="تأكيد حذف الوظيفة"
                                      data-confirm-btn="حذف"
                                      data-confirm-type="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm">حذف</button>
                                </form>

                                <div x-show="showMembers" x-transition.opacity class="fixed inset-0 z-[950] flex items-center justify-center p-4" style="display:none;" @keydown.escape.window="showMembers = false">
                                    <div class="absolute inset-0 bg-black/50" @click="showMembers = false"></div>
                                    <div class="relative w-full max-w-2xl rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-hidden">
                                        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                                            <div class="text-right">
                                                <p class="text-base font-extrabold text-slate-800">الموظفون داخل وظيفة {{ $jobTitle->name_ar }}</p>
                                                <p class="text-xs text-slate-500 mt-1">إجمالي {{ $jobTitle->employees->count() }} موظف</p>
                                            </div>
                                            <button type="button" class="btn-ghost btn-sm" @click="showMembers = false">إغلاق</button>
                                        </div>

                                        <div class="max-h-[65vh] overflow-y-auto p-4 space-y-2">
                                            @forelse($jobTitle->employees->sortBy('name') as $member)
                                                <div class="rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-right">
                                                    <p class="text-sm font-bold text-slate-800">{{ $member->name }}</p>
                                                    <p class="text-xs text-slate-500 mt-1">رقم الموظف: {{ $member->ac_no ?: 'غير محدد' }}</p>
                                                    <p class="text-xs text-slate-500 mt-1">الوظيفة: {{ $member->position_line ?: 'غير محدد' }}</p>
                                                    <p class="text-xs text-slate-500 mt-1">القسم: {{ $member->department?->name ?: 'غير محدد' }}</p>
                                                </div>
                                            @empty
                                                <p class="text-sm text-slate-500 text-center py-6">لا يوجد موظفون داخل هذه الوظيفة.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
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
