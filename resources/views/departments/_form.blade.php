@php
    $selectedEmployeeIds = collect(old('employee_ids', ($department?->employees?->pluck('id')->all() ?? [])))
        ->map(fn ($id) => (int) $id)
        ->all();

    $selectedManagerId = old('manager_employee_id', $department?->manager_employee_id);
@endphp

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
            <option value="{{ $employee->id }}" @selected((string) $selectedManagerId === (string) $employee->id)>
                {{ $employee->name }}
            </option>
        @endforeach
    </select>
    @error('manager_employee_id')<p class="form-error">{{ $message }}</p>@enderror
</div>

<div class="form-group">
    <label class="form-label">أعضاء القسم</label>
    <select name="employee_ids[]" multiple size="8" class="form-input @error('employee_ids') border-red-400 @enderror @error('employee_ids.*') border-red-400 @enderror">
        @foreach($employees as $employee)
            <option value="{{ $employee->id }}" @selected(in_array((int) $employee->id, $selectedEmployeeIds, true))>
                {{ $employee->name }}
            </option>
        @endforeach
    </select>
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
