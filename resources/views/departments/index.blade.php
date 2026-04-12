@extends('layouts.app')

@section('title', 'الأقسام')
@section('page-title', 'إدارة الأقسام')
@section('page-subtitle', 'إنشاء وتعديل الأقسام ومديريها')

@section('content')
<div class="section-header">
    <div>
        <h1 class="section-title">الأقسام</h1>
        <p class="section-subtitle">{{ $departments->total() }} قسم</p>
    </div>
    <a href="{{ route('departments.create') }}" class="btn-primary">إضافة قسم</a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>القسم</th>
                    <th>المدير</th>
                    <th>عدد الأعضاء</th>
                    <th>الحالة</th>
                    <th class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $department)
                    <tr x-data="{ showMembers: false }">
                        <td>
                            <p class="font-semibold text-slate-800">{{ $department->name }}</p>
                        </td>
                        <td>{{ $department->managerEmployee?->name ?: 'غير محدد' }}</td>
                        <td><span class="badge-gray">{{ $department->employees_count }}</span></td>
                        <td>
                            @if($department->is_active)
                                <span class="badge-success">نشط</span>
                            @else
                                <span class="badge-danger">غير نشط</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" class="btn-ghost btn-sm" @click="showMembers = true">عرض</button>
                                <a href="{{ route('departments.edit', $department) }}" class="btn-ghost btn-sm">تعديل</a>
                                <form action="{{ route('departments.destroy', $department) }}" method="POST"
                                      data-confirm="هل تريد حذف القسم {{ $department->name }}؟"
                                      data-confirm-title="تأكيد الحذف"
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
                                                <p class="text-base font-extrabold text-slate-800">الموظفون داخل قسم {{ $department->name }}</p>
                                                <p class="text-xs text-slate-500 mt-1">إجمالي {{ $department->employees->count() }} موظف</p>
                                            </div>
                                            <button type="button" class="btn-ghost btn-sm" @click="showMembers = false">إغلاق</button>
                                        </div>

                                        <div class="max-h-[65vh] overflow-y-auto p-4 space-y-2">
                                            @forelse($department->employees->sortBy('name') as $member)
                                                <div class="rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-right">
                                                    <p class="text-sm font-bold text-slate-800">{{ $member->name }}</p>
                                                    <p class="text-xs text-slate-500 mt-1">رقم الموظف: {{ $member->ac_no ?: 'غير محدد' }}</p>
                                                    <p class="text-xs text-slate-500 mt-1">الوظيفة: {{ $member->position_line ?: 'غير محدد' }}</p>
                                                    <p class="text-xs text-slate-500 mt-1">القسم: {{ $department->name }}</p>
                                                </div>
                                            @empty
                                                <p class="text-sm text-slate-500 text-center py-6">لا يوجد موظفون داخل هذا القسم.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-10 text-slate-500">لا توجد أقسام مضافة بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($departments->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">{{ $departments->links() }}</div>
    @endif
</div>
@endsection
