@extends('layouts.app')

@section('title', 'إضافة قسم')
@section('page-title', 'إضافة قسم جديد')
@section('page-subtitle', 'تحديد المدير وأعضاء القسم')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="card p-6">
        <form action="{{ route('departments.store') }}" method="POST" class="space-y-5">
            @csrf
            @php($department = null)
            @include('departments._form', ['department' => $department])

            <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                <button type="submit" name="submit_action" value="save" class="btn-primary">حفظ القسم</button>
                <button type="submit" name="submit_action" value="save_and_add_new" class="btn-ghost">حفظ وإضافة قسم آخر</button>
                <a href="{{ route('departments.index') }}" class="btn-ghost">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
