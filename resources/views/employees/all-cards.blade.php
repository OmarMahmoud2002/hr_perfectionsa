@extends('layouts.app')

@section('title', 'عرض كل الموظفين')
@section('page-title', 'عرض كل الموظفين')
@section('page-subtitle', 'استعراض جميع الموظفين في شكل كروت')

@section('content')
<div class="space-y-5">
    <div class="section-header">
        <div>
            <h1 class="section-title">عرض كل الموظفين</h1>
            <p class="section-subtitle">{{ $employees->total() }} موظف في النظام</p>
        </div>
    </div>

    <div class="card p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
            <div class="md:col-span-10">
                <label class="form-label">بحث</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="ابحث بالاسم أو رقم الموظف..." class="form-input">
            </div>
            <div class="md:col-span-2 flex md:justify-end gap-2">
                <button type="submit" class="btn-primary btn-sm">بحث</button>
                @if($search !== '')
                    <a href="{{ route('employees.all-cards') }}" class="btn-ghost btn-sm">إعادة ضبط</a>
                @endif
            </div>
        </form>
    </div>

    @if($employees->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($employees as $employee)
                @php
                    $avatarUrl = $employee->user?->profile?->avatar_path
                        ? route('media.avatar', ['path' => $employee->user->profile->avatar_path])
                        : null;
                @endphp
                <div x-data="{ open: false }" class="card card-interactive p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-16 h-16 rounded-2xl overflow-hidden border border-slate-200 flex-shrink-0"
                             style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                            @if($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ $employee->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-white text-xl font-black">
                                    {{ mb_substr($employee->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <button type="button" @click="open = !open" class="text-right w-full text-base font-bold text-slate-800 hover:text-[#31719d] transition">
                                {{ $employee->name }}
                            </button>
                            <p class="text-xs text-slate-500 mt-1">{{ $employee->position_line }}</p>
                        </div>
                    </div>

                    <div x-show="open" x-transition class="mt-4 pt-4 border-t border-slate-100 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2">
                                <p class="text-[11px] font-semibold text-slate-500">القسم</p>
                                <p class="text-sm text-slate-700 mt-1">{{ $employee->department?->name ?: 'غير محدد' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2">
                                <p class="text-[11px] font-semibold text-slate-500">رقم الموظف</p>
                                <p class="text-sm text-slate-700 mt-1">{{ $employee->ac_no ?: 'غير محدد' }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-500 mb-1">Bio</p>
                            <p class="text-sm text-slate-700 leading-6">{{ $employee->user?->profile?->bio ?: 'لا يوجد نبذة شخصية.' }}</p>
                        </div>

                        <div class="rounded-xl border border-slate-100 bg-white px-3 py-2.5 space-y-1.5">
                            <p class="text-xs font-semibold text-slate-500">البيانات التعريفية</p>
                            <p class="text-sm text-slate-700">
                                <span class="font-medium text-slate-600">المسمى:</span>
                                {{ $employee->job_title_label ?: 'غير محدد' }}
                            </p>
                            <p class="text-sm text-slate-700">
                                <span class="font-medium text-slate-600">البريد:</span>
                                {{ $employee->user?->email ?: 'لا يوجد بريد مرتبط' }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-xs font-semibold text-slate-500">روابط التواصل</p>
                            @if($employee->user?->profile?->social_link_1)
                                <a href="{{ $employee->user->profile->social_link_1 }}" target="_blank" rel="noopener noreferrer" class="text-xs text-[#31719d] hover:underline break-all block">{{ $employee->user->profile->social_link_1 }}</a>
                            @endif
                            @if($employee->user?->profile?->social_link_2)
                                <a href="{{ $employee->user->profile->social_link_2 }}" target="_blank" rel="noopener noreferrer" class="text-xs text-[#31719d] hover:underline break-all block">{{ $employee->user->profile->social_link_2 }}</a>
                            @endif
                            @if(!$employee->user?->profile?->social_link_1 && !$employee->user?->profile?->social_link_2)
                                <p class="text-xs text-slate-400">لا توجد روابط تواصل.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($employees->hasPages())
            <div class="mt-4">
                {{ $employees->links() }}
            </div>
        @endif
    @else
        <div class="empty-state animate-fade-in">
            <div class="empty-state-icon animate-float-soft">
                <svg class="w-10 h-10" style="color: #4596cf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-700 mb-2">لا يوجد موظفون</h3>
            <p class="text-slate-500 text-sm mb-1">لا توجد نتائج مطابقة للفلاتر الحالية.</p>
        </div>
    @endif
</div>
@endsection
