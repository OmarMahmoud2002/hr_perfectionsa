<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Department\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $departmentService,
    ) {}

    public function index(): View
    {
        $departments = Department::query()
            ->with(['managerEmployee:id,name', 'employees:id,department_id'])
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        $employees = Employee::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);

        return view('departments.create', compact('employees'));
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
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
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);

        return view('departments.edit', compact('department', 'employees'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $validated = $request->validated();
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
}
