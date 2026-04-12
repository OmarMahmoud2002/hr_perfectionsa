<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Department\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $departmentService,
    ) {}

    public function index(): View
    {
        $departments = Department::query()
            ->with([
                'managerEmployee:id,name',
                'employees:id,name,ac_no,department_id,job_title_id,job_title',
                'employees.jobTitleRef:id,name_ar',
            ])
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        $employees = Employee::query()
            ->with(['department:id,name', 'jobTitleRef:id,name_ar'])
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'department_id', 'job_title_id', 'job_title']);

        return view('departments.create', compact('employees'));
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $selectedEmployeeIds = $this->collectSelectedEmployeeIds($validated);
        $conflicts = $this->collectDepartmentReassignmentConflicts($selectedEmployeeIds);

        if ($conflicts->isNotEmpty() && ! $request->boolean('confirm_reassignment')) {
            return back()
                ->withInput()
                ->withErrors([
                    'employee_ids' => 'تم اكتشاف موظفين مرتبطين مسبقا بأقسام. يلزم تأكيد النقل لإتمام العملية.',
                ]);
        }

        $validated['assigned_by_user_id'] = (int) $request->user()->id;

        $this->departmentService->create($validated);

        if ((string) $request->input('submit_action') === 'save_and_add_new') {
            return redirect()
                ->route('departments.create')
                ->with('success', 'تم إنشاء القسم بنجاح. يمكنك إضافة قسم آخر الآن.');
        }

        return redirect()
            ->route('departments.index')
            ->with('success', 'تم إنشاء القسم بنجاح.');
    }

    public function edit(Department $department): View
    {
        $department->loadMissing(['managerEmployee:id,name', 'employees:id,name,department_id']);

        $employees = Employee::query()
            ->with(['department:id,name', 'jobTitleRef:id,name_ar'])
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'department_id', 'job_title_id', 'job_title']);

        return view('departments.edit', compact('department', 'employees'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $validated = $request->validated();
        $selectedEmployeeIds = $this->collectSelectedEmployeeIds($validated);
        $conflicts = $this->collectDepartmentReassignmentConflicts($selectedEmployeeIds);

        if ($conflicts->isNotEmpty() && ! $request->boolean('confirm_reassignment')) {
            return back()
                ->withInput()
                ->withErrors([
                    'employee_ids' => 'تم اكتشاف موظفين مرتبطين مسبقا بأقسام. يلزم تأكيد النقل لإتمام العملية.',
                ]);
        }

        $validated['assigned_by_user_id'] = (int) $request->user()->id;

        $this->departmentService->update($department, $validated);

        return back()->with('success', 'تم تحديث القسم بنجاح.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->departmentService->delete($department);

        return redirect()
            ->route('departments.index')
            ->with('success', 'تم حذف القسم بنجاح.');
    }

    /**
     * @param array<string,mixed> $validated
     * @return array<int,int>
     */
    private function collectSelectedEmployeeIds(array $validated): array
    {
        $employeeIds = collect((array) ($validated['employee_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        $managerId = isset($validated['manager_employee_id']) ? (int) $validated['manager_employee_id'] : 0;
        if ($managerId > 0) {
            $employeeIds->push($managerId);
        }

        return $employeeIds->unique()->values()->all();
    }

    /**
     * @param array<int,int> $employeeIds
     */
    private function collectDepartmentReassignmentConflicts(array $employeeIds): Collection
    {
        if (empty($employeeIds)) {
            return collect();
        }

        return Employee::query()
            ->whereIn('id', $employeeIds)
            ->whereNotNull('department_id')
            ->with('department:id,name')
            ->get(['id', 'name', 'department_id'])
            ->map(function (Employee $employee): array {
                return [
                    'id' => (int) $employee->id,
                    'name' => (string) $employee->name,
                    'current_department' => (string) ($employee->department?->name ?? 'غير محدد'),
                ];
            });
    }
}
