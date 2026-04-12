@extends('layouts.app')

@section('title', 'تأكيد الاستيراد')
@section('page-title', 'تأكيد الاستيراد')
@section('page-subtitle', 'مراجعة الإعدادات والإجازات قبل تنفيذ الاستيراد')

@section('content')
<div id="import-confirm-page"
    class="space-y-6 animate-fade-in"
    data-requires-replacement="{{ $existingBatch ? '1' : '0' }}"
    data-import-month="{{ $batch->month_name }}"
    data-import-year="{{ $batch->year }}">

    {{-- معاينة الدفعة --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-slate-500 text-sm font-medium">الشهر</span>
                <div class="w-9 h-9 bg-secondary-100 rounded-xl flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-slate-800">{{ $batch->month_name }}</p>
            <p class="text-sm text-slate-500 mt-0.5">{{ $batch->year }}</p>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-slate-500 text-sm font-medium">الموظفون</span>
                <div class="w-9 h-9 bg-primary-100 rounded-xl flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-slate-800">{{ $batch->employees_count }}</p>
            <p class="text-sm text-slate-500 mt-0.5">موظف في الملف</p>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-slate-500 text-sm font-medium">السجلات</span>
                <div class="w-9 h-9 bg-gold-100 rounded-xl flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-slate-800">{{ number_format($batch->records_count) }}</p>
            <p class="text-sm text-slate-500 mt-0.5">سجل حضور</p>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-slate-500 text-sm font-medium">الإجازات الرسمية</span>
                <div class="w-9 h-9 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-4.5 h-4.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-slate-800">{{ $batch->publicHolidays->count() }}</p>
            <p class="text-sm text-slate-500 mt-0.5">إجازة مضافة</p>
        </div>
    </div>

    {{-- تحذير البيانات المكررة --}}
    @if($existingBatch)
    <div class="bg-amber-50 border border-amber-300 rounded-2xl p-5">
        <div class="flex gap-4">
            <div class="w-10 h-10 bg-amber-200 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-amber-800 mb-1">⚠️ توجد بيانات سابقة لنفس الشهر</h4>
                <p class="text-amber-700 text-sm leading-relaxed">
                    يوجد استيراد مكتمل لشهر <strong>{{ $batch->month_name }} {{ $batch->year }}</strong>
                    بتاريخ {{ $existingBatch->created_at->format('Y/m/d') }}
                    يحتوي على <strong>{{ number_format($existingBatch->records_count) }}</strong> سجل.
                </p>
                <p class="text-amber-700 text-sm mt-2">
                    إذا اخترت <strong>الاستبدال</strong> سيتم حذف البيانات القديمة نهائياً واستبدالها بالبيانات الجديدة.
                </p>
                <div class="mt-3 flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" id="replaceToggle" name="replace_existing" value="1"
                               form="confirmForm"
                               class="w-4 h-4 text-amber-600 rounded border-amber-400 focus:ring-amber-500">
                        <span class="text-amber-800 font-semibold text-sm">نعم، أريد الاستبدال (حذف البيانات القديمة)</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    @endif

    <form action="{{ route('import.confirm', $batch->id) }}" method="POST" id="confirmForm"
          data-loading="true" data-loading-target="#confirmBtn" data-loading-text="جاري الاستيراد...">
        @csrf

        @if(!$existingBatch)
            <input type="hidden" name="replace_existing" value="0">
        @endif
    </form>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- إعدادات الاستيراد --}}
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-header-title text-base">إعدادات الحساب</h3>
                        <p class="text-xs text-white/70 mt-0.5">تخصيص لهذا الشهر فقط (اختياري)</p>
                    </div>
                    <span class="card-header-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                </div>
                <div class="card-body space-y-5">

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        {{-- وقت بدء العمل --}}
                        <div class="form-group">
                            <label class="form-label">
                                وقت بدء العمل
                                <span class="text-xs text-slate-400 font-normal mr-1">({{ $defaultSettings['work_start_time'] }})</span>
                            </label>
                            <input type="time" name="work_start_time"
                                   form="confirmForm"
                                   value="{{ old('work_start_time', $batchSettings['work_start_time'] ?? '') }}"
                                   class="form-input" placeholder="{{ $defaultSettings['work_start_time'] }}">
                            <p class="text-xs text-slate-400 mt-1">الافتراضي: {{ $defaultSettings['work_start_time'] }}</p>
                            @error('work_start_time') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- وقت انتهاء العمل --}}
                        <div class="form-group">
                            <label class="form-label">
                                وقت انتهاء العمل
                                <span class="text-xs text-slate-400 font-normal mr-1">({{ $defaultSettings['work_end_time'] }})</span>
                            </label>
                            <input type="time" name="work_end_time"
                                   form="confirmForm"
                                   value="{{ old('work_end_time', $batchSettings['work_end_time'] ?? '') }}"
                                   class="form-input" placeholder="{{ $defaultSettings['work_end_time'] }}">
                            <p class="text-xs text-slate-400 mt-1">الافتراضي: {{ $defaultSettings['work_end_time'] }}</p>
                            @error('work_end_time') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- وقت بدء الـ Overtime --}}
                        <div class="form-group">
                            <label class="form-label">
                                بدء الأوفرتايم
                                <span class="text-xs text-slate-400 font-normal mr-1">({{ $defaultSettings['overtime_start_time'] }})</span>
                            </label>
                            <input type="time" name="overtime_start_time"
                                   form="confirmForm"
                                   value="{{ old('overtime_start_time', $batchSettings['overtime_start_time'] ?? '') }}"
                                   class="form-input" placeholder="{{ $defaultSettings['overtime_start_time'] }}">
                            <p class="text-xs text-slate-400 mt-1">الافتراضي: {{ $defaultSettings['overtime_start_time'] }}</p>
                            @error('overtime_start_time') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- فترة السماح بالتأخير --}}
                        <div class="form-group">
                            <label class="form-label">
                                فترة السماح للتأخير
                                <span class="text-xs text-slate-400 font-normal mr-1">({{ $defaultSettings['late_grace_minutes'] }} د)</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="late_grace_minutes" min="0" max="120" step="5"
                                       form="confirmForm"
                                       value="{{ old('late_grace_minutes', $batchSettings['late_grace_minutes'] ?? '') }}"
                                       class="form-input pl-10"
                                       placeholder="{{ $defaultSettings['late_grace_minutes'] }}">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">دقيقة</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">تأخير مسموح به بدون خصم</p>
                            @error('late_grace_minutes') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <p class="text-xs text-slate-500 leading-relaxed">
                            <svg class="w-3.5 h-3.5 inline ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            اترك الحقول فارغة لاستخدام الإعدادات الافتراضية. يمكنك تعديل افتراضيات الإجازات من صفحة <a href="{{ route('leave.approvals.employee-settings') }}" class="text-secondary-600 hover:underline">لوحة إعدادات الموظفين</a>.
                        </p>
                    </div>
                </div>
            </div>

            {{-- الإجازات الرسمية --}}
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-header-title text-base">الإجازات الرسمية</h3>
                        <p class="text-xs text-white/70 mt-0.5">اختياري — أضف إجازات لاستثنائها من الحساب إن وجدت</p>
                    </div>
                    <span class="card-header-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>
                <div class="card-body space-y-4">

                    {{-- إضافة إجازة --}}
                    <form action="{{ route('holidays.store', $batch->id) }}" method="POST" class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                        @csrf
                        <p class="text-sm font-semibold text-slate-700 mb-3">إضافة إجازة رسمية</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="form-group mb-0">
                                <label class="form-label text-xs">التاريخ</label>
                                <input type="date" name="date"
                                       value="{{ old('date') }}"
                                       class="form-input @error('date') border-red-400 @enderror" required>
                                @error('date') <p class="form-error text-xs">{{ $message }}</p> @enderror
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label text-xs">اسم الإجازة</label>
                                <input type="text" name="name"
                                       value="{{ old('name') }}"
                                       class="form-input @error('name') border-red-400 @enderror"
                                       placeholder="مثال: عيد الأضحى" required>
                                @error('name') <p class="form-error text-xs">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="flex justify-end mt-3">
                            <button type="submit" class="btn btn-teal btn-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                إضافة
                            </button>
                        </div>
                    </form>

                    {{-- قائمة الإجازات --}}
                    @if($batch->publicHolidays->isEmpty())
                        <div class="text-center py-6 text-slate-400">
                            <svg class="w-10 h-10 mx-auto mb-2 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm">لم تُضَف إجازات رسمية بعد</p>
                        </div>
                    @else
                        <div class="space-y-2 max-h-72 overflow-y-auto scrollbar-thin">
                            @foreach($batch->publicHolidays->sortBy('date') as $holiday)
                            <div class="flex items-center justify-between p-3 bg-red-50 rounded-xl border border-red-100 group hover:bg-red-100 transition-colors">
                                <div>
                                    <p class="font-semibold text-slate-800 text-sm">{{ $holiday->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $holiday->date->format('Y/m/d') }} — {{ $holiday->date->locale('ar')->dayName }}</p>
                                </div>
                                <form action="{{ route('holidays.destroy', [$batch->id, $holiday->id]) }}" method="POST"
                                      data-confirm="حذف إجازة {{ $holiday->name }}؟"
                                      data-confirm-title="حذف إجازة"
                                      data-confirm-btn="حذف"
                                      data-confirm-type="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg text-red-400 hover:text-red-600 hover:bg-red-200 transition-all opacity-0 group-hover:opacity-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- أزرار التأكيد --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-5 bg-white rounded-2xl border border-slate-200 shadow-card mt-6">
            <a href="{{ route('import.form') }}" class="btn btn-ghost order-2 sm:order-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                العودة
            </a>

            <div class="flex items-center gap-3 order-1 sm:order-2">
                <form action="{{ route('import.destroy', $batch->id) }}" method="POST"
                      data-confirm="إلغاء هذا الاستيراد وحذف الملف؟"
                      data-confirm-title="إلغاء الاستيراد"
                      data-confirm-btn="إلغاء"
                      data-confirm-type="warning">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline text-red-500 border-red-200 hover:bg-red-50">
                        إلغاء الاستيراد
                    </button>
                </form>

                <button type="{{ $existingBatch ? 'button' : 'submit' }}" form="confirmForm" id="confirmBtn"
                        class="btn btn-primary btn-lg"
                        @if($existingBatch) onclick="handleConfirmImport()" @endif>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    تأكيد الاستيراد
                </button>
            </div>
        </div>

</div>
@endsection

@push('scripts')
<script>
    const pageRoot = document.getElementById('import-confirm-page');
    const requiresReplacementConfirmation = pageRoot?.dataset.requiresReplacement === '1';
    const importMonthName = pageRoot?.dataset.importMonth || '';
    const importYear = pageRoot?.dataset.importYear || '';

    function handleConfirmImport() {
        const checkbox = document.getElementById('replaceToggle');
        if (!checkbox?.checked) {
            // تمييز منطقة التحقق
            const warningBox = checkbox?.closest('.bg-amber-50');
            if (warningBox) {
                warningBox.style.outline = '3px solid #f59e0b';
                warningBox.style.borderRadius = '12px';
                setTimeout(() => { warningBox.style.outline = ''; }, 1600);
                warningBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            showConfirm({
                title:    'يجب تأكيد الاستبدال',
                message:  'يوجد بيانات سابقة لهذا الشهر. يرجى تأكيد خيار الاستبدال أولاً قبل المتابعة.',
                type:     'warning',
                infoOnly: true,
                cancelText: 'حسناً',
            });
            return;
        }
        showConfirm({
            title:       'تأكيد استبدال البيانات',
            message:     `سيتم حذف بيانات شهر ${importMonthName} ${importYear} القديمة نهائياً واستبدالها بالبيانات الجديدة.`,
            confirmText: 'نعم، استبدل',
            type:        'danger',
            onConfirm: function () {
                const btn = document.getElementById('confirmBtn');
                btn.disabled = true;
                btn.innerHTML = `<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> جاري الاستيراد...`;
                document.getElementById('confirmForm').submit();
            },
        });
    }

    if (!requiresReplacementConfirmation) {
        document.getElementById('confirmForm').addEventListener('submit', function () {
            const btn = document.getElementById('confirmBtn');
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                جاري الاستيراد...
            `;
        });
    }
</script>
@endpush
