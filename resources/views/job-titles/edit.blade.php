@extends('layouts.app')

@section('title', 'تعديل وظيفة')
@section('page-title', 'تعديل وظيفة')
@section('page-subtitle', $jobTitle->name_ar)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="card p-6">
        <form action="{{ route('job-titles.update', $jobTitle) }}" method="POST" class="space-y-5" id="job-title-form">
            @csrf
            @method('PUT')
            @include('job-titles._form', ['jobTitle' => $jobTitle, 'employees' => $employees])

            <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                <button type="submit" class="btn-primary">حفظ التعديلات</button>
                <a href="{{ route('job-titles.index') }}" class="btn-ghost">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection

@include('partials.reassignment-confirmation-script', ['formId' => 'job-title-form', 'mode' => 'job_title'])
