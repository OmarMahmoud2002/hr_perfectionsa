@extends('layouts.app')

@section('title', 'سجل الاستيرادات')
@section('page-title', 'سجل الاستيرادات')
@section('page-subtitle', 'عرض جميع دفعات الاستيراد السابقة')

@section('content')
<div class="space-y-6 animate-fade-in">

    {{-- Header Actions --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('import.form') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                العودة للاستيراد
            </a>
        </div>
        <a href="{{ route('import.form') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            استيراد جديد
        </a>
    </div>

    {{-- Stats Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-slate-500 text-sm font-medium">إجمالي الاستيرادات</span>
                <div class="w-9 h-9 bg-secondary-100 rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-slate-800">{{ $batches->total() }}</p>
            <p class="text-sm text-slate-500 mt-0.5">دفعة استيراد</p>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-slate-500 text-sm font-medium">المكتملة</span>
                <div class="w-9 h-9 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-slate-800">{{ $batches->getCollection()->where('status.value', 'completed')->count() }}</p>
            <p class="text-sm text-slate-500 mt-0.5">في هذه الصفحة</p>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-slate-500 text-sm font-medium">إجمالي السجلات</span>
                <div class="w-9 h-9 bg-primary-100 rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-slate-800">{{ number_format($batches->getCollection()->sum('records_count')) }}</p>
            <p class="text-sm text-slate-500 mt-0.5">سجل حضور في هذه الصفحة</p>
        </div>
    </div>

    {{-- Batches Table --}}
    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-secondary-600 rounded-xl flex items-center justify-center shadow-md">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">جميع دفعات الاستيراد</h3>
                    <p class="text-xs text-white/70">مرتبة من الأحدث للأقدم</p>
                </div>
            </div>
        </div>

        @if($batches->isEmpty())
            <div class="card-body">
                <div class="text-center py-16 text-slate-400">
                    <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                    </div>
                    <p class="text-lg font-semibold text-slate-500 mb-1">لا توجد استيرادات بعد</p>
                    <p class="text-sm text-slate-400 mb-4">قم برفع أول ملف حضور للبدء</p>
                    <a href="{{ route('import.form') }}" class="btn btn-primary btn-sm">
                        رفع ملف الآن
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="table-th">الشهر / السنة</th>
                            <th class="table-th">اسم الملف</th>
                            <th class="table-th text-center">الموظفون</th>
                            <th class="table-th text-center">السجلات</th>
                            <th class="table-th text-center">الإجازات</th>
                            <th class="table-th text-center">الحالة</th>
                            <th class="table-th">رُفع بواسطة</th>
                            <th class="table-th">تاريخ الرفع</th>
                            <th class="table-th text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($batches as $batch)
                        <tr class="hover:bg-slate-50/60 transition-colors group">
                            <td class="table-td">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-secondary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800">{{ $batch->month_name }}</p>
                                        <p class="text-xs text-slate-500">{{ $batch->year }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-td">
                                <span class="text-slate-600 text-sm truncate max-w-[180px] block" title="{{ $batch->file_name }}">
                                    {{ $batch->file_name }}
                                </span>
                            </td>
                            <td class="table-td text-center">
                                <span class="badge badge-teal">{{ $batch->employees_count ?? 0 }}</span>
                            </td>
                            <td class="table-td text-center">
                                <span class="font-semibold text-slate-700">{{ number_format($batch->records_count ?? 0) }}</span>
                            </td>
                            <td class="table-td text-center">
                                @if($batch->publicHolidays && $batch->publicHolidays->count() > 0)
                                    <span class="badge badge-danger">{{ $batch->publicHolidays->count() }}</span>
                                @else
                                    <span class="text-slate-300 text-sm">—</span>
                                @endif
                            </td>
                            <td class="table-td text-center">
                                @switch($batch->status->value)
                                    @case('completed')
                                        <span class="badge badge-success">مكتمل</span>
                                        @break
                                    @case('pending')
                                        <span class="badge badge-warning">في الانتظار</span>
                                        @break
                                    @case('processing')
                                        <span class="badge badge-blue">جاري المعالجة</span>
                                        @break
                                    @case('failed')
                                        <span class="badge badge-danger">فشل</span>
                                        @break
                                    @default
                                        <span class="badge badge-gray">{{ $batch->status->value }}</span>
                                @endswitch
                            </td>
                            <td class="table-td">
                                <span class="text-slate-600 text-sm">{{ $batch->uploader?->name ?? '—' }}</span>
                            </td>
                            <td class="table-td">
                                <span class="text-slate-500 text-sm">{{ $batch->created_at->format('Y/m/d') }}</span>
                                <p class="text-xs text-slate-400">{{ $batch->created_at->format('H:i') }}</p>
                            </td>
                            <td class="table-td">
                                <div class="flex items-center justify-center gap-2">
                                    @if($batch->status->value === 'pending')
                                        <a href="{{ route('import.confirm.show', $batch->id) }}"
                                           class="btn btn-primary btn-sm">
                                            متابعة
                                        </a>
                                    @elseif($batch->status->value === 'completed')
                                        <a href="{{ route('attendance.index') }}?batch={{ $batch->id }}"
                                           class="btn btn-outline btn-sm"
                                           title="عرض سجلات الحضور">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            عرض
                                        </a>
                                    @endif

                                    @if($batch->import_settings && count($batch->import_settings))
                                        <button type="button"
                                                onclick="showSettings({{ $batch->id }})"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-secondary-600 hover:bg-secondary-50 transition-all"
                                                title="إعدادات الاستيراد">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </button>
                                    @endif

                                    <form action="{{ route('import.destroy', $batch->id) }}" method="POST"
                                        data-confirm="هل أنت متأكد من حذف بيانات شهر {{ $batch->month_name }} {{ $batch->year }}؟ لا يمكن التراجع عن هذا الإجراء."
                                        data-confirm-title="حذف الدفعة"
                                        data-confirm-btn="حذف"
                                        data-confirm-type="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100"
                                                title="حذف">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- Settings Detail Row (hidden by default) --}}
                        @if($batch->import_settings && count($batch->import_settings))
                        <tr id="settings-row-{{ $batch->id }}" class="hidden bg-slate-50/80">
                            <td colspan="9" class="px-6 py-4">
                                <div class="flex flex-wrap gap-3">
                                    <p class="text-xs font-bold text-slate-500 w-full mb-1">إعدادات مخصصة لهذا الشهر:</p>
                                    @foreach($batch->import_settings as $key => $value)
                                    <span class="inline-flex items-center gap-1.5 bg-white border border-slate-200 text-slate-600 text-xs px-3 py-1.5 rounded-lg">
                                        <span class="font-semibold text-slate-400">{{ [
                                            'work_start_time'     => 'بداية العمل',
                                            'work_end_time'       => 'نهاية العمل',
                                            'overtime_start_time' => 'بداية OT',
                                            'late_grace_minutes'  => 'فترة السماح',
                                        ][$key] ?? $key }}</span>:
                                        {{ $value }}{{ $key === 'late_grace_minutes' ? ' د' : '' }}
                                    </span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($batches->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $batches->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    function showSettings(batchId) {
        const row = document.getElementById('settings-row-' + batchId);
        if (row) {
            row.classList.toggle('hidden');
        }
    }
</script>
@endpush
