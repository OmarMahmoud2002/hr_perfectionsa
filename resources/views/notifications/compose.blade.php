@extends('layouts.app')

@section('title', 'إرسال إشعار')
@section('page-title', 'إرسال إشعار')
@section('page-subtitle', 'إرسال رسالة داخلية تصل داخل النظام وعلى البريد الإلكتروني للحسابات المتاحة')

@section('content')
@php
    $summary = $composeSummary ?? ['employees' => 0, 'departments' => 0, 'job_titles' => 0];
    $audienceType = old('audience_type', 'all');
    $selectedEmployees = collect(old('employee_ids', []))->map(fn ($id) => (int) $id)->all();
    $selectedDepartments = collect(old('department_ids', []))->map(fn ($id) => (int) $id)->all();
    $selectedJobTitles = collect(old('job_title_ids', []))->map(fn ($id) => (int) $id)->all();
@endphp

<div class="space-y-6">
    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
        <div class="px-5 py-6 sm:px-6"
             style="background:
                radial-gradient(circle at top right, rgba(69,150,207,.16), transparent 32%),
                radial-gradient(circle at left center, rgba(77,155,151,.14), transparent 28%),
                linear-gradient(135deg, #f8fbff 0%, #f2f8f7 52%, #f8fafc 100%);">
                <div class="grid gap-4 lg:grid-cols-[1.6fr_.9fr] lg:items-center">
                <div>
                    <h2 class="mt-3 text-2xl font-black text-slate-900">إشعار جماعي بشكل أوضح وأسرع</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                        اختر الفئة المستهدفة، اكتب الرسالة، وأضف رابطًا أو صورة إن احتجت. الإشعار سيظهر داخل النظام، وسيصل أيضًا عبر البريد للحسابات التي لديها بريد إلكتروني صالح.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 text-center shadow-sm">
                        <p class="text-[11px] font-bold text-slate-500">حسابات الموظفين</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ $summary['employees'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-4 text-center shadow-sm">
                        <p class="text-[11px] font-bold text-emerald-700">الأقسام</p>
                        <p class="mt-2 text-2xl font-black text-emerald-900">{{ $summary['departments'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4 text-center shadow-sm">
                        <p class="text-[11px] font-bold text-amber-700">الوظائف</p>
                        <p class="mt-2 text-2xl font-black text-amber-900">{{ $summary['job_titles'] }}</p>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('notifications.sent.index') }}" class="btn-ghost !rounded-2xl !border !border-slate-200 !bg-white">
                    سجل الرسائل المرسلة
                </a>
            </div>
        </div>
    </section>

    <form action="{{ route('notifications.compose.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-5 xl:grid-cols-12">
        @csrf

        <section class="card overflow-hidden xl:col-span-7">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h3 class="text-lg font-black text-slate-900">من سيستلم الإشعار؟</h3>
                <p class="mt-1 text-sm text-slate-500">اختر طريقة التحديد المناسبة ثم حدد الأشخاص أو المجموعات المطلوبة.</p>
            </div>

            <div class="space-y-5 px-5 py-5 sm:px-6">
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="group relative block cursor-pointer">
                        <input type="radio" name="audience_type" value="all" class="peer sr-only" @checked($audienceType === 'all')>
                        <span data-audience-card="all" class="flex h-full rounded-3xl border p-4 transition-all duration-200 {{ $audienceType === 'all' ? 'border-sky-500 bg-sky-50 shadow-md shadow-sky-100' : 'border-slate-200 bg-white' }}">
                            <span>
                                <span class="block text-sm font-black text-slate-900">كل الموظفين</span>
                                <span class="mt-1 block text-xs leading-6 text-slate-500">إرسال الإشعار لكل الموظفين الذين لديهم حسابات نشطة.</span>
                            </span>
                        </span>
                    </label>

                    <label class="group relative block cursor-pointer">
                        <input type="radio" name="audience_type" value="employees" class="peer sr-only" @checked($audienceType === 'employees')>
                        <span data-audience-card="employees" class="flex h-full rounded-3xl border p-4 transition-all duration-200 {{ $audienceType === 'employees' ? 'border-sky-500 bg-sky-50 shadow-md shadow-sky-100' : 'border-slate-200 bg-white' }}">
                            <span>
                                <span class="block text-sm font-black text-slate-900">موظفون محددون</span>
                                <span class="mt-1 block text-xs leading-6 text-slate-500">اختيار يدوي لعدة موظفين بالاسم والقسم والوظيفة.</span>
                            </span>
                        </span>
                    </label>

                    <label class="group relative block cursor-pointer">
                        <input type="radio" name="audience_type" value="departments" class="peer sr-only" @checked($audienceType === 'departments')>
                        <span data-audience-card="departments" class="flex h-full rounded-3xl border p-4 transition-all duration-200 {{ $audienceType === 'departments' ? 'border-sky-500 bg-sky-50 shadow-md shadow-sky-100' : 'border-slate-200 bg-white' }}">
                            <span>
                                <span class="block text-sm font-black text-slate-900">حسب القسم</span>
                                <span class="mt-1 block text-xs leading-6 text-slate-500">إرسال الإشعار لكل الموظفين داخل قسم أو أكثر.</span>
                            </span>
                        </span>
                    </label>

                    <label class="group relative block cursor-pointer">
                        <input type="radio" name="audience_type" value="job_titles" class="peer sr-only" @checked($audienceType === 'job_titles')>
                        <span data-audience-card="job_titles" class="flex h-full rounded-3xl border p-4 transition-all duration-200 {{ $audienceType === 'job_titles' ? 'border-sky-500 bg-sky-50 shadow-md shadow-sky-100' : 'border-slate-200 bg-white' }}">
                            <span>
                                <span class="block text-sm font-black text-slate-900">حسب الوظيفة</span>
                                <span class="mt-1 block text-xs leading-6 text-slate-500">اختيار المسمى الوظيفي ليصل الإشعار لكل من ينتمي له.</span>
                            </span>
                        </span>
                    </label>
                </div>

                <div data-audience-panel="all" class="{{ $audienceType === 'all' ? '' : 'hidden' }}">
                    <div class="rounded-3xl border border-sky-100 bg-sky-50/80 p-5">
                        <h4 class="text-sm font-black text-sky-900">سيتم الإرسال إلى كل الحسابات النشطة</h4>
                        <p class="mt-2 text-sm leading-7 text-sky-800/80">هذه الطريقة مناسبة للإعلانات العامة والتنبيهات التي تخص كل الموظفين داخل النظام.</p>
                    </div>
                </div>

                <div data-audience-panel="employees" class="space-y-4 {{ $audienceType === 'employees' ? '' : 'hidden' }}">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                        <label for="employee_search" class="form-label">بحث داخل الموظفين</label>
                        <input id="employee_search" type="text" class="form-input !rounded-2xl !border-slate-200 !bg-white" placeholder="ابحث بالاسم أو القسم أو الوظيفة..." data-employee-search>
                    </div>

                    <div class="max-h-[420px] overflow-y-auto rounded-3xl border border-slate-200 bg-white p-3">
                        <div class="grid gap-3">
                            @forelse($employees as $employee)
                                @php
                                    $deptName = $employee->department?->name ?? 'بدون قسم';
                                    $jobTitle = $employee->jobTitleRef?->name_ar ?? 'بدون وظيفة';
                                    $searchText = mb_strtolower($employee->name.' '.$deptName.' '.$jobTitle);
                                @endphp
                                <label class="employee-option cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-sky-200 hover:bg-sky-50/50"
                                       data-search="{{ $searchText }}">
                                    <span class="flex items-start gap-3">
                                        <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}"
                                               class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-400"
                                               @checked(in_array($employee->id, $selectedEmployees, true))>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-black text-slate-900">{{ $employee->name }}</span>
                                            <span class="mt-1 block text-xs text-slate-500">{{ $jobTitle }}</span>
                                            <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $deptName }}</span>
                                        </span>
                                    </span>
                                </label>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                                    لا توجد حسابات موظفين متاحة حاليًا.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div data-audience-panel="departments" class="{{ $audienceType === 'departments' ? '' : 'hidden' }}">
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach($departments as $department)
                            <label class="cursor-pointer rounded-3xl border border-slate-200 bg-white p-4 transition hover:border-emerald-200 hover:bg-emerald-50/50">
                                <span class="flex items-start gap-3">
                                    <input type="checkbox" name="department_ids[]" value="{{ $department->id }}"
                                           class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-400"
                                           @checked(in_array($department->id, $selectedDepartments, true))>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-black text-slate-900">{{ $department->name }}</span>
                                        <span class="mt-2 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                                            {{ $department->employees_with_accounts_count }} حساب متاح
                                        </span>
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div data-audience-panel="job_titles" class="{{ $audienceType === 'job_titles' ? '' : 'hidden' }}">
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach($jobTitles as $jobTitle)
                            <label class="cursor-pointer rounded-3xl border border-slate-200 bg-white p-4 transition hover:border-amber-200 hover:bg-amber-50/50">
                                <span class="flex items-start gap-3">
                                    <input type="checkbox" name="job_title_ids[]" value="{{ $jobTitle->id }}"
                                           class="mt-1 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-400"
                                           @checked(in_array($jobTitle->id, $selectedJobTitles, true))>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-black text-slate-900">{{ $jobTitle->name_ar }}</span>
                                        <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-700">
                                            {{ $jobTitle->employees_with_accounts_count }} حساب متاح
                                        </span>
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <aside class="space-y-5 xl:col-span-5">
            <section class="card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-black text-slate-900">محتوى الإشعار</h3>
                    <p class="mt-1 text-sm text-slate-500">اكتب الرسالة التي ستصل للموظفين وأضف تفاصيل إضافية إذا لزم الأمر.</p>
                </div>

                <div class="space-y-4 px-5 py-5">
                    <div>
                        <label for="title" class="form-label">عنوان مختصر <span class="text-slate-400 font-normal">(اختياري)</span></label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" maxlength="120"
                               class="form-input !rounded-2xl !border-slate-200 !bg-slate-50/80"
                               placeholder="مثال: تحديث مهم بخصوص الدوام" data-preview-title>
                    </div>

                    <div>
                        <label for="message" class="form-label">رسالة الإشعار</label>
                        <textarea id="message" name="message" rows="7"
                                  class="form-input !rounded-2xl !border-slate-200 !bg-slate-50/80"
                                  placeholder="اكتب الرسالة كاملة هنا..." data-preview-message>{{ old('message') }}</textarea>
                    </div>

                    <div>
                        <label for="link_url" class="form-label">رابط إضافي <span class="text-slate-400 font-normal">(اختياري)</span></label>
                        <input id="link_url" name="link_url" type="text" value="{{ old('link_url') }}"
                               class="form-input ltr-input !rounded-2xl !border-slate-200 !bg-slate-50/80"
                               placeholder="https://example.com" data-preview-link>
                    </div>

                    <div>
                        <label for="image" class="form-label">صورة مرفقة <span class="text-slate-400 font-normal">(اختياري)</span></label>
                        <input id="image" name="image" type="file" accept="image/*"
                               class="form-input !rounded-2xl !border-slate-200 !bg-slate-50/80"
                               data-preview-image>
                        <p class="mt-1 text-xs text-slate-500">الصورة ستظهر داخل صفحة الإشعار بعد فتحه من الموظف.</p>
                    </div>
                </div>
            </section>

            <section class="card overflow-hidden xl:sticky xl:top-6">
                <div class="border-b border-slate-100 px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-black text-slate-900">معاينة مباشرة</h3>
                            <p class="mt-1 text-sm text-slate-500">كيف سيظهر الإشعار قبل الإرسال.</p>
                        </div>
                        <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-[11px] font-black text-sky-700" data-preview-audience>
                            كل الموظفين
                        </span>
                    </div>
                </div>

                <div class="space-y-4 px-5 py-5">
                    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl text-white shadow-sm"
                                 style="background: linear-gradient(135deg, #31719d 0%, #317c77 100%);">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m8-8L3 11l7 2 2 7L21 4z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-black text-slate-900" data-preview-title-output>إشعار جديد من الإدارة</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">من {{ auth()->user()->name }}</p>
                                <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600" data-preview-message-output>اكتب الرسالة لتظهر المعاينة هنا.</p>
                                <a href="#" class="mt-3 hidden inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-700" data-preview-link-output>
                                    فتح الرابط
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-slate-50" data-preview-image-wrapper>
                        <img src="" alt="معاينة الصورة" class="max-h-[360px] w-full object-contain bg-white" data-preview-image-output>
                    </div>
                </div>
            </section>
        </aside>

        <div class="xl:col-span-12">
            <div class="card px-5 py-4 sm:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm leading-7 text-slate-500">
                        سيتم إرسال الإشعار داخل النظام فورًا، وسيصل بريد إلكتروني تلقائي للحسابات التي لديها بريد إلكتروني مسجل.
                    </p>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('notifications.index') }}" class="btn-ghost justify-center !rounded-2xl !border !border-slate-200 !bg-white">
                            الرجوع للإشعارات
                        </a>
                        <button type="submit" class="btn-primary justify-center !rounded-2xl px-6">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m8-8L3 11l7 2 2 7L21 4z"/>
                            </svg>
                            إرسال الإشعار الآن
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const radios = Array.from(document.querySelectorAll('input[name="audience_type"]'));
        const audienceCards = Array.from(document.querySelectorAll('[data-audience-card]'));
        const panels = Array.from(document.querySelectorAll('[data-audience-panel]'));
        const previewAudience = document.querySelector('[data-preview-audience]');
        const employeeSearch = document.querySelector('[data-employee-search]');
        const employeeOptions = Array.from(document.querySelectorAll('.employee-option'));

        const titleInput = document.querySelector('[data-preview-title]');
        const titleOutput = document.querySelector('[data-preview-title-output]');
        const messageInput = document.querySelector('[data-preview-message]');
        const messageOutput = document.querySelector('[data-preview-message-output]');
        const linkInput = document.querySelector('[data-preview-link]');
        const linkOutput = document.querySelector('[data-preview-link-output]');
        const imageInput = document.querySelector('[data-preview-image]');
        const imageWrapper = document.querySelector('[data-preview-image-wrapper]');
        const imageOutput = document.querySelector('[data-preview-image-output]');

        function selectedAudienceLabel() {
            const checked = document.querySelector('input[name="audience_type"]:checked');
            if (!checked) return 'كل الموظفين';

            if (checked.value === 'employees') {
                const count = document.querySelectorAll('input[name="employee_ids[]"]:checked').length;
                return count > 0 ? ('موظفون محددون: ' + count) : 'موظفون محددون';
            }

            if (checked.value === 'departments') {
                const count = document.querySelectorAll('input[name="department_ids[]"]:checked').length;
                return count > 0 ? ('أقسام محددة: ' + count) : 'حسب القسم';
            }

            if (checked.value === 'job_titles') {
                const count = document.querySelectorAll('input[name="job_title_ids[]"]:checked').length;
                return count > 0 ? ('وظائف محددة: ' + count) : 'حسب الوظيفة';
            }

            return 'كل الموظفين';
        }

        function updateAudienceCards() {
            const selected = document.querySelector('input[name="audience_type"]:checked')?.value || 'all';

            audienceCards.forEach((card) => {
                const isActive = card.getAttribute('data-audience-card') === selected;
                card.classList.toggle('border-sky-500', isActive);
                card.classList.toggle('bg-sky-50', isActive);
                card.classList.toggle('shadow-md', isActive);
                card.classList.toggle('shadow-sky-100', isActive);
                card.classList.toggle('border-slate-200', !isActive);
                card.classList.toggle('bg-white', !isActive);
            });
        }

        function updateAudiencePanels() {
            const selected = document.querySelector('input[name="audience_type"]:checked')?.value || 'all';
            panels.forEach((panel) => {
                const isActive = panel.getAttribute('data-audience-panel') === selected;
                panel.classList.toggle('hidden', !isActive);
                if (isActive) {
                    panel.classList.remove('animate-fade-in');
                    requestAnimationFrame(() => panel.classList.add('animate-fade-in'));
                }
            });

            if (previewAudience) {
                previewAudience.textContent = selectedAudienceLabel();
            }

            updateAudienceCards();
        }

        function updatePreview() {
            const title = (titleInput?.value || '').trim();
            const message = (messageInput?.value || '').trim();
            const link = (linkInput?.value || '').trim();

            if (titleOutput) {
                titleOutput.textContent = title || 'إشعار جديد من الإدارة';
            }

            if (messageOutput) {
                messageOutput.textContent = message || 'اكتب الرسالة لتظهر المعاينة هنا.';
            }

            if (linkOutput) {
                const hasLink = link !== '';
                linkOutput.classList.toggle('hidden', !hasLink);
                if (hasLink) {
                    linkOutput.setAttribute('href', link);
                    linkOutput.textContent = 'فتح الرابط المرفق';
                }
            }

            if (previewAudience) {
                previewAudience.textContent = selectedAudienceLabel();
            }
        }

        function updateImagePreview() {
            const file = imageInput?.files?.[0];
            if (!file || !imageWrapper || !imageOutput) {
                if (imageWrapper) imageWrapper.classList.add('hidden');
                if (imageOutput) imageOutput.setAttribute('src', '');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                imageOutput.setAttribute('src', event.target?.result || '');
                imageWrapper.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }

        if (employeeSearch) {
            employeeSearch.addEventListener('input', function () {
                const query = (employeeSearch.value || '').trim().toLowerCase();
                employeeOptions.forEach((option) => {
                    const haystack = option.getAttribute('data-search') || '';
                    option.classList.toggle('hidden', query !== '' && !haystack.includes(query));
                });
            });
        }

        radios.forEach((radio) => radio.addEventListener('change', updateAudiencePanels));
        document.querySelectorAll('input[name="employee_ids[]"], input[name="department_ids[]"], input[name="job_title_ids[]"]').forEach((input) => {
            input.addEventListener('change', updateAudiencePanels);
        });
        titleInput?.addEventListener('input', updatePreview);
        messageInput?.addEventListener('input', updatePreview);
        linkInput?.addEventListener('input', updatePreview);
        imageInput?.addEventListener('change', updateImagePreview);

        updateAudiencePanels();
        updatePreview();
    })();
</script>
@endpush
