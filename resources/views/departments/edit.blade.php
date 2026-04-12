@extends('layouts.app')

@section('title', 'تعديل القسم')
@section('page-title', 'تعديل القسم')
@section('page-subtitle', $department->name)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="card p-6">
        <form action="{{ route('departments.update', $department) }}" method="POST" class="space-y-5" id="department-form">
            @csrf
            @method('PUT')
            @include('departments._form', ['department' => $department])

            <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                <button type="submit" class="btn-primary">حفظ التعديلات</button>
                <a href="{{ route('departments.index') }}" class="btn-ghost">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection

@include('partials.reassignment-confirmation-script', ['formId' => 'department-form', 'mode' => 'department'])
