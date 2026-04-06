@extends('layouts.app')

@section('title', 'إدارة المواقع')
@section('page-title', 'إدارة المواقع')
@section('page-subtitle', 'إضافة وتعديل مواقع الحضور المعتمدة')

@section('content')

<div class="section-header">
    <div>
        <h1 class="section-title">المواقع</h1>
        <p class="section-subtitle">{{ $locations->total() }} موقع داخل النظام</p>
    </div>
    <a href="{{ route('locations.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        إضافة موقع
    </a>
</div>

@if($locations->count() > 0)
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>اسم الموقع</th>
                    <th>دائرة العرض</th>
                    <th>خط الطول</th>
                    <th>نطاق السماح</th>
                    <th>رابط Google Maps</th>
                    <th class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($locations as $location)
                <tr>
                    <td>
                        <p class="font-semibold text-slate-800">{{ $location->name }}</p>
                    </td>
                    <td>
                        <span class="font-mono text-sm bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg">{{ number_format((float) $location->latitude, 7) }}</span>
                    </td>
                    <td>
                        <span class="font-mono text-sm bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg">{{ number_format((float) $location->longitude, 7) }}</span>
                    </td>
                    <td>
                        <span class="badge-success">{{ number_format((int) $location->radius) }} متر</span>
                    </td>
                    <td>
                        @if($location->google_maps_url)
                            <a href="{{ $location->google_maps_url }}" target="_blank" rel="noopener noreferrer" class="text-sm text-sky-700 hover:underline">
                                فتح الرابط
                            </a>
                        @else
                            <span class="text-xs text-slate-400">غير مضاف</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('locations.edit', $location) }}"
                               class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition"
                               title="تعديل">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>

                            <form action="{{ route('locations.destroy', $location) }}" method="POST"
                                  data-confirm="هل تريد حذف الموقع «{{ $location->name }}»؟"
                                  data-confirm-title="تأكيد حذف الموقع"
                                  data-confirm-btn="حذف"
                                  data-confirm-type="warning">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50 transition"
                                        title="حذف">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-5-3h4a1 1 0 011 1v2H9V5a1 1 0 011-1z"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($locations->hasPages())
    <div class="px-6 py-4 border-t border-slate-100">
        {{ $locations->links() }}
    </div>
    @endif
</div>
@else
<div class="card p-16 text-center">
    <div class="w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-4"
         style="background: rgba(69, 150, 207, 0.1);">
        <svg class="w-10 h-10" style="color: #4596cf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.243-4.243a8 8 0 1111.313 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
    </div>
    <h3 class="text-lg font-bold text-slate-700 mb-2">لا توجد مواقع مسجلة</h3>
    <p class="text-slate-500 text-sm mb-6">أضف أول موقع لتحديد نطاق الحضور الجغرافي.</p>
    <a href="{{ route('locations.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        إضافة أول موقع
    </a>
</div>
@endif

@endsection
