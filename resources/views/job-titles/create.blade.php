@extends('layouts.app')

@section('title', 'إضافة وظيفة')
@section('page-title', 'إضافة وظيفة جديدة')
@section('page-subtitle', 'إدخال بيانات الوظيفة وربط الموظفين اختياريًا')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="card p-6">
        <form action="{{ route('job-titles.store') }}" method="POST" class="space-y-5" id="job-title-form">
            @csrf
            @php($jobTitle = null)
            @include('job-titles._form', ['jobTitle' => $jobTitle, 'employees' => $employees])

            <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                <button type="submit" name="submit_action" value="save" class="btn-primary">حفظ الوظيفة</button>
                <button type="submit" name="submit_action" value="save_and_add_new" class="btn-ghost">حفظ وإضافة وظيفة أخرى</button>
                <a href="{{ route('job-titles.index') }}" class="btn-ghost">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection

@include('partials.reassignment-confirmation-script', ['formId' => 'job-title-form', 'mode' => 'job_title'])
