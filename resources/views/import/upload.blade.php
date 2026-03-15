@extends('layouts.app')

@section('title', 'استيراد بيانات الحضور')
@section('page-title', 'استيراد بيانات الحضور')
@section('page-subtitle', 'رفع ملف Excel وتحليل بيانات الحضور والانصراف')

@section('content')
<div class="space-y-6 animate-fade-in">

    {{-- رفع الملف --}}
    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-secondary-600 rounded-xl flex items-center justify-center shadow-md flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">رفع ملف Excel</h3>
                    <p class="text-xs text-white/70">يدعم صيغ .xlsx و .xls بحجم أقصى 10MB</p>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm"
                data-loading="true" data-loading-target="#uploadBtn" data-loading-text="جاري الرفع...">
                @csrf

                {{-- منطقة السحب والإفلات --}}
                <div
                    id="dropZone"
                    class="relative border-2 border-dashed border-slate-300 rounded-2xl p-10 text-center cursor-pointer transition-all duration-300 hover:border-secondary-400 hover:bg-secondary-50 group"
                    onclick="document.getElementById('fileInput').click()"
                    ondragover="handleDragOver(event)"
                    ondragleave="handleDragLeave(event)"
                    ondrop="handleDrop(event)"
                >
                    <input
                        type="file"
                        id="fileInput"
                        name="file"
                        accept=".xlsx,.xls"
                        class="sr-only"
                        onchange="handleFileSelect(this)"
                    >

                    {{-- حالة افتراضية --}}
                    <div id="dropZoneDefault">
                        <div class="w-16 h-16 bg-slate-100 group-hover:bg-secondary-100 rounded-2xl flex items-center justify-center mx-auto mb-4 transition-colors">
                            <svg class="w-8 h-8 text-slate-400 group-hover:text-secondary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-slate-600 font-semibold text-lg mb-1">اسحب وأفلت ملف Excel هنا</p>
                        <p class="text-slate-400 text-sm mb-4">أو انقر لاختيار الملف</p>
                        <div class="flex items-center justify-center gap-3">
                            <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-500 text-xs px-3 py-1.5 rounded-lg font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                xlsx
                            </span>
                            <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-500 text-xs px-3 py-1.5 rounded-lg font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                xls
                            </span>
                            <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-500 text-xs px-3 py-1.5 rounded-lg font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                حجم أقصى 10MB
                            </span>
                        </div>
                    </div>

                    {{-- حالة الملف المختار --}}
                    <div id="dropZoneSelected" class="hidden">
                        <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-slate-700 font-semibold text-lg mb-1" id="selectedFileName">-</p>
                        <p class="text-slate-400 text-sm" id="selectedFileSize">-</p>
                    </div>
                </div>

                @error('file')
                    <p class="form-error mt-2">{{ $message }}</p>
                @enderror

                {{-- تعليمات تنسيق الملف --}}
                <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-amber-800 font-semibold text-sm mb-1">تنسيق الملف المطلوب</p>
                            <p class="text-amber-700 text-xs leading-relaxed">
                                يجب أن يحتوي الملف على الأعمدة: <strong>رقم الموظف</strong>، <strong>اسم الموظف</strong>، <strong>التاريخ</strong>، <strong>وقت الدخول</strong>، <strong>وقت الخروج</strong>.
                                يمكن أن تكون أسماء الأعمدة بأي لغة (سيتم اكتشافها تلقائياً).
                            </p>
                        </div>
                    </div>
                </div>

                {{-- زر الرفع --}}
                <div class="flex justify-end mt-6">
                    <button type="submit" id="uploadBtn" class="btn btn-primary btn-lg" disabled>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        رفع الملف وتحليله
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- سجل الاستيرادات --}}
    <div class="card">
        <div class="card-header">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center shadow-md">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">سجل الاستيرادات</h3>
                        <p class="text-xs text-white/70">جميع الدفعات المستوردة</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($batches->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                    <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <p class="text-slate-500 font-semibold text-lg mb-1">لا توجد استيرادات سابقة</p>
                    <p class="text-slate-400 text-sm">ارفع أول ملف Excel للبدء.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>الشهر / السنة</th>
                                <th>اسم الملف</th>
                                <th>الموظفون</th>
                                <th>السجلات</th>
                                <th>الحالة</th>
                                <th>رُفع بواسطة</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($batches as $batch)
                            <tr>
                                <td>
                                    <span class="font-bold text-slate-800">{{ $batch->month_name }}</span>
                                    <span class="text-slate-500 text-sm mr-1">{{ $batch->year }}</span>
                                </td>
                                <td>
                                    <span class="text-slate-600 text-sm truncate max-w-[200px] block" title="{{ $batch->file_name }}">
                                        {{ $batch->file_name }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-teal">{{ $batch->employees_count ?? 0 }}</span>
                                </td>
                                <td>
                                    <span class="font-medium text-slate-700">{{ number_format($batch->records_count ?? 0) }}</span>
                                </td>
                                <td>
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
                                <td class="text-slate-600 text-sm">{{ $batch->uploader?->name ?? '—' }}</td>
                                <td class="text-slate-500 text-sm">{{ $batch->created_at->format('Y/m/d') }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @if($batch->status->value === 'pending')
                                            <a href="{{ route('import.confirm.show', $batch->id) }}"
                                               class="btn btn-primary btn-sm">
                                                متابعة
                                            </a>
                                        @elseif($batch->status->value === 'completed')
                                            <a href="{{ route('attendance.index') }}?batch={{ $batch->id }}"
                                               class="btn btn-outline btn-sm">
                                                عرض
                                            </a>
                                        @endif

                                        <form action="{{ route('import.destroy', $batch->id) }}" method="POST"
                                              data-confirm="هل أنت متأكد من حذف بيانات شهر {{ $batch->month_name }} {{ $batch->year }}؟ لا يمكن التراجع عن هذا الإجراء."
                                              data-confirm-title="حذف الدفعة"
                                              data-confirm-btn="حذف"
                                              data-confirm-type="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
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

</div>
@endsection

@push('scripts')
<script>
    const dropZone      = document.getElementById('dropZone');
    const dropDefault   = document.getElementById('dropZoneDefault');
    const dropSelected  = document.getElementById('dropZoneSelected');
    const fileNameEl    = document.getElementById('selectedFileName');
    const fileSizeEl    = document.getElementById('selectedFileSize');
    const uploadBtn     = document.getElementById('uploadBtn');

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function setFile(file) {
        if (!file) return;
        fileNameEl.textContent = file.name;
        fileSizeEl.textContent = formatSize(file.size);
        dropDefault.classList.add('hidden');
        dropSelected.classList.remove('hidden');
        dropZone.classList.remove('border-slate-300');
        dropZone.classList.add('border-green-400', 'bg-green-50');
        uploadBtn.disabled = false;
    }

    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            setFile(input.files[0]);
        }
    }

    function handleDragOver(e) {
        e.preventDefault();
        dropZone.classList.add('border-secondary-400', 'bg-secondary-50');
    }

    function handleDragLeave(e) {
        dropZone.classList.remove('border-secondary-400', 'bg-secondary-50');
    }

    function handleDrop(e) {
        e.preventDefault();
        dropZone.classList.remove('border-secondary-400', 'bg-secondary-50');
        const file = e.dataTransfer.files[0];
        if (file) {
            const input = document.getElementById('fileInput');
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            setFile(file);
        }
    }

    // Loading state on submit
    document.getElementById('uploadForm').addEventListener('submit', function() {
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = `
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            جاري الرفع والتحليل...
        `;
    });
</script>
@endpush
