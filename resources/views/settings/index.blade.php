@extends('layouts.app')

@section('title', 'إعدادات النظام')
@section('page-title', 'إعدادات النظام')
@section('page-subtitle', 'ضبط معاملات الحضور والانصراف والمرتبات')

@section('content')
<div class="max-w-3xl mx-auto animate-fade-in">

    <form action="{{ route('settings.update') }}" method="POST" id="settings-form"
          data-loading="true" data-loading-target="#settings-save" data-loading-text="جاري الحفظ..."
          data-confirm="هل تريد حفظ هذه الإعدادات؟ التغييرات ستؤثر على الحسابات الجديدة فقط."
          data-confirm-title="تأكيد الحفظ" data-confirm-btn="حفظ" data-confirm-type="warning">
        @csrf
        @method('PUT')

        <div class="space-y-4 sm:space-y-6">

            {{-- ===== قسم أوقات الدوام ===== --}}
            <div class="card overflow-hidden">
                <div class="card-header">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 bg-secondary-600 rounded-xl flex items-center justify-center shadow-md flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-white">أوقات الدوام الرسمي</h3>
                            <p class="text-xs text-white/70 hidden sm:block">توقيتات بدء وانتهاء العمل والأوفرتايم</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 sm:gap-5">

                        <div class="form-group">
                            <label class="form-label">وقت بدء العمل</label>
                            <input type="time" name="work_start_time"
                                   value="{{ old('work_start_time', $settings['work_start_time']) }}"
                                   class="form-input" required>
                            @error('work_start_time')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">وقت انتهاء العمل</label>
                            <input type="time" name="work_end_time"
                                   value="{{ old('work_end_time', $settings['work_end_time']) }}"
                                   class="form-input" required>
                            @error('work_end_time')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">بدء احتساب الأوفرتايم</label>
                            <input type="time" name="overtime_start_time"
                                   value="{{ old('overtime_start_time', $settings['overtime_start_time']) }}"
                                   class="form-input" required>
                            <p class="text-xs text-slate-400 mt-1">فترة السماح بعد نهاية الدوام</p>
                            @error('overtime_start_time')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">فترة السماح للتأخير</label>
                            <div class="relative">
                                <input type="number" name="late_grace_minutes" min="0" max="120" step="5"
                                       value="{{ old('late_grace_minutes', $settings['late_grace_minutes'] ?? 30) }}"
                                       class="form-input ltr-input pl-12" required>
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">دقيقة</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">تأخير مسموح قبل الخصم (0 = بدون سماح)</p>
                            @error('late_grace_minutes')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    {{-- مثال توضيحي --}}
                    <div class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <p class="text-xs text-slate-500 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            مثال: دوام من
                            <strong id="preview-start">{{ $settings['work_start_time'] }}</strong>
                            إلى
                            <strong id="preview-end">{{ $settings['work_end_time'] }}</strong>
                            — الأوفرتايم يبدأ بعد
                            <strong id="preview-ot">{{ $settings['overtime_start_time'] }}</strong>
                        </p>
                    </div>
                </div>
            </div>

            {{-- ===== قسم قيم الخصم والمكافأة ===== --}}
            <div class="card overflow-hidden">
                <div class="card-header">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 bg-gold-500 rounded-xl flex items-center justify-center shadow-md flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-white">قيم الخصم والمكافأة</h3>
                            <p class="text-xs text-white/70 hidden sm:block">المبالغ المباشرة بالجنيه لكل ساعة / يوم</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">

                        {{-- خصم ساعة التأخير --}}
                        <div class="form-group">
                            <label class="form-label">
                                خصم ساعة التأخير
                                <span class="form-hint">بالجنيه لكل ساعة</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="late_deduction_per_hour" step="0.01" min="0"
                                       value="{{ old('late_deduction_per_hour', $settings['late_deduction_per_hour'] ?? 0) }}"
                                       class="form-input ltr-input pl-12" required>
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">ج.م</span>
                            </div>
                            @error('late_deduction_per_hour')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- خصم يوم الغياب --}}
                        <div class="form-group">
                            <label class="form-label">
                                خصم يوم الغياب
                                <span class="form-hint">بالجنيه لكل يوم</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="absent_deduction_per_day" step="0.01" min="0"
                                       value="{{ old('absent_deduction_per_day', $settings['absent_deduction_per_day'] ?? 0) }}"
                                       class="form-input ltr-input pl-12" required>
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">ج.م</span>
                            </div>
                            @error('absent_deduction_per_day')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- مكافأة ساعة الأوفرتايم --}}
                        <div class="form-group">
                            <label class="form-label">
                                مكافأة ساعة الأوفرتايم
                                <span class="form-hint">بالجنيه لكل ساعة</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="overtime_rate_per_hour" step="0.01" min="0"
                                       value="{{ old('overtime_rate_per_hour', $settings['overtime_rate_per_hour'] ?? 0) }}"
                                       class="form-input ltr-input pl-12" required>
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">ج.م</span>
                            </div>
                            @error('overtime_rate_per_hour')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    {{-- صندوق توضيحي لمعادلة الراتب --}}
                    <div class="mt-4 p-3 sm:p-4 bg-blue-50 border border-blue-200 rounded-xl">
                        <p class="text-xs font-bold text-blue-700 mb-2 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01"/>
                            </svg>
                            معادلة الراتب النهائي
                        </p>
                        <p class="text-xs text-blue-600 leading-relaxed font-mono">
                            الراتب = الأساسي − (ساعات التأخير × خصم الساعة) − (أيام الغياب × خصم اليوم) + (ساعات OT × مكافأة الساعة)
                        </p>
                        <p class="text-xs text-blue-500 mt-1">
                            ملاحظة: أول يوم غياب في كل أسبوع يُحتسب إجازة أسبوعية ولا يُخصم.
                        </p>
                    </div>
                </div>
            </div>

            {{-- ===== قسم قواعد الحضور ===== --}}
            <div class="card overflow-hidden">
                <div class="card-header">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 bg-primary-600 rounded-xl flex items-center justify-center shadow-md flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-white">قواعد الحضور</h3>
                            <p class="text-xs text-white/70 hidden sm:block">أيام وساعات العمل الأساسية</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-4 sm:gap-5">

                        <div class="form-group">
                            <label class="form-label">أيام العمل في الشهر</label>
                            <input type="number" name="working_days_per_month" min="1" max="31"
                                   value="{{ old('working_days_per_month', $settings['working_days_per_month']) }}"
                                   class="form-input" required>
                            <p class="text-xs text-slate-400 mt-1">لحساب الراتب اليومي</p>
                            @error('working_days_per_month')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">ساعات العمل في اليوم</label>
                            <input type="number" name="working_hours_per_day" min="1" max="24" step="0.5"
                                   value="{{ old('working_hours_per_day', $settings['working_hours_per_day']) }}"
                                   class="form-input" required>
                            <p class="text-xs text-slate-400 mt-1">لحساب الراتب الساعي</p>
                            @error('working_hours_per_day')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    {{-- حساب الراتب اليومي التوضيحي --}}
                    <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <p class="text-xs text-emerald-700 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            مثال: موظف راتبه 5000 ÷
                            <strong>{{ $settings['working_days_per_month'] }}</strong> يوم =
                            <strong>{{ number_format(5000 / max((float)$settings['working_days_per_month'], 1), 1) }}</strong>
                            جنيه/يوم
                        </p>
                    </div>
                </div>
            </div>

            {{-- ===== زر الحفظ ===== --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <p class="text-xs text-slate-400 text-center sm:text-right">
                    <svg class="w-3.5 h-3.5 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    التغييرات تؤثر على الحسابات الجديدة فقط — الرواتب المحسوبة لا تتأثر
                </p>
                <button type="submit"
                        id="settings-save"
                        class="btn btn-primary w-full sm:w-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    حفظ الإعدادات
                </button>
            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
    // تحديث المثال التوضيحي لأوقات الدوام
    function updatePreview() {
        const startVal = document.querySelector('[name="work_start_time"]').value;
        const endVal   = document.querySelector('[name="work_end_time"]').value;
        const otVal    = document.querySelector('[name="overtime_start_time"]').value;

        const previewStart = document.getElementById('preview-start');
        const previewEnd   = document.getElementById('preview-end');
        const previewOt    = document.getElementById('preview-ot');

        if (previewStart) previewStart.textContent = startVal || '—';
        if (previewEnd)   previewEnd.textContent   = endVal   || '—';
        if (previewOt)    previewOt.textContent     = otVal    || '—';
    }

    // ربط الأحداث
    document.addEventListener('DOMContentLoaded', function() {
        ['work_start_time', 'work_end_time', 'overtime_start_time'].forEach(function(name) {
            const el = document.querySelector('[name="' + name + '"]');
            if (el) el.addEventListener('change', updatePreview);
        });
    });

</script>
@endpush
