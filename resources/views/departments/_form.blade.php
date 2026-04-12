@php
    $selectedEmployeeIds = collect(old('employee_ids', ($department?->employees?->pluck('id')->all() ?? [])))
        ->map(fn ($id) => (int) $id)
        ->all();

    $selectedManagerId = old('manager_employee_id', $department?->manager_employee_id);
@endphp

<input type="hidden" name="confirm_reassignment" value="0" data-confirm-reassignment-flag>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="form-group">
        <label class="form-label">اسم القسم <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $department?->name) }}" class="form-input @error('name') border-red-400 @enderror" required>
        @error('name')<p class="form-error">{{ $message }}</p>@enderror
    </div>
</div>

<div class="form-group">
    <label class="form-label">مدير القسم</label>
    <select name="manager_employee_id" class="form-input @error('manager_employee_id') border-red-400 @enderror">
        <option value="">بدون مدير قسم</option>
        @foreach($employees as $employee)
            <option value="{{ $employee->id }}"
                    data-current-department-id="{{ $employee->department_id }}"
                    data-current-department-name="{{ $employee->department?->name ?? '' }}"
                    @selected((string) $selectedManagerId === (string) $employee->id)>
                {{ $employee->name }}
            </option>
        @endforeach
    </select>
    @error('manager_employee_id')<p class="form-error">{{ $message }}</p>@enderror
</div>

<div class="form-group">
    <label class="form-label">أعضاء القسم</label>
    <div class="rounded-xl border border-slate-200 p-2 max-h-72 overflow-y-auto space-y-1.5 @error('employee_ids') border-red-400 @enderror @error('employee_ids.*') border-red-400 @enderror">
        @foreach($employees as $employee)
            <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 cursor-pointer hover:bg-slate-50 transition">
                <input type="checkbox"
                       name="employee_ids[]"
                       value="{{ $employee->id }}"
                       data-employee-name="{{ $employee->name }}"
                       data-current-department-id="{{ $employee->department_id }}"
                       data-current-department-name="{{ $employee->department?->name ?? '' }}"
                       class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-300"
                       @checked(in_array((int) $employee->id, $selectedEmployeeIds, true))>
                <span class="flex-1 text-sm text-slate-700">
                    <span class="font-semibold text-slate-800">{{ $employee->name }}</span>
                    <span class="text-slate-500"> — {{ $employee->position_line ?: 'غير محدد' }}</span>
                </span>
            </label>
        @endforeach
    </div>
    <p class="text-xs text-slate-400 mt-1">يمكن اختيار أكثر من موظف. إذا تم تعيين مدير قسم، سيُعامل كمدير لهذا القسم.</p>
    @error('employee_ids')<p class="form-error">{{ $message }}</p>@enderror
    @error('employee_ids.*')<p class="form-error">{{ $message }}</p>@enderror
</div>

<div class="form-group">
    <label class="inline-flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300" @checked(old('is_active', $department?->is_active ?? true))>
        <span class="text-sm font-medium text-slate-700">القسم نشط</span>
    </label>
</div>
