@php
    $selectedEmployeeIds = collect(old('employee_ids', ($jobTitle?->employees?->pluck('id')->all() ?? [])))
        ->map(fn ($id) => (int) $id)
        ->all();

    $roleOptions = [
        'employee' => 'موظف',
        'user' => 'مراجع',
        'office_girl' => 'عامل مكتبي',
        'hr' => 'الموارد البشرية',
        'manager' => 'مدير',
        'admin' => 'مسؤول نظام',
    ];
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="form-group">
            <label class="form-label">اسم الوظيفة <span class="text-red-500">*</span></label>
            <input type="text" name="name_ar" value="{{ old('name_ar', $jobTitle?->name_ar) }}" class="form-input @error('name_ar') border-red-400 @enderror" required>
            @error('name_ar')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label class="form-label">ربط الدور بالنظام (اختياري)</label>
            <select name="system_role_mapping" class="form-input @error('system_role_mapping') border-red-400 @enderror">
                <option value="">بدون ربط</option>
                @foreach($roleOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('system_role_mapping', $jobTitle?->system_role_mapping) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('system_role_mapping')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">الموظفون داخل هذه الوظيفة (اختياري)</label>
        <input type="hidden" name="manage_employee_assignments" value="1">
        <select name="employee_ids[]" multiple size="8" class="form-input @error('employee_ids') border-red-400 @enderror @error('employee_ids.*') border-red-400 @enderror">
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}" @selected(in_array((int) $employee->id, $selectedEmployeeIds, true))>
                    {{ $employee->name }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-slate-400 mt-1">يمكن اختيار أكثر من موظف. إذا ألغيت الاختيار ثم حفظت، سيتم فك الربط.</p>
        @error('employee_ids')<p class="form-error">{{ $message }}</p>@enderror
        @error('employee_ids.*')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-group">
        <label class="inline-flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300" @checked(old('is_active', $jobTitle?->is_active ?? true))>
            <span class="text-sm font-medium text-slate-700">الوظيفة نشطة</span>
        </label>
    </div>
</div>
