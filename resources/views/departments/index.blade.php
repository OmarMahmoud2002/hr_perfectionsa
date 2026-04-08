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
                    <tr>
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
