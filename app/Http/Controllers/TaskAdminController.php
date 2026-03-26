<?php

namespace App\Http\Controllers;

use App\Exports\TasksEvaluationsExport;
use App\Models\Employee;
use App\Models\EmployeeMonthTask;
use App\Services\EmployeeOfMonth\TaskManagementService;
use App\Services\Payroll\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class TaskAdminController extends Controller
{
    public function __construct(
        private readonly TaskManagementService $taskManagementService,
    ) {}

    public function index(Request $request): View
    {
        $period = PayrollPeriod::monthForDate(now());
        $month = (int) $request->input('month', (int) $request->session()->get('tasks.admin.filters.month', $period['month']));
        $year = (int) $request->input('year', (int) $request->session()->get('tasks.admin.filters.year', $period['year']));
        $taskDate = $request->input('task_date');
        $employeeId = (int) $request->input('employee_id', 0);

        $request->session()->put('tasks.admin.filters.month', $month);
        $request->session()->put('tasks.admin.filters.year', $year);

        $tasksQuery = EmployeeMonthTask::query()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->with(['creator:id,name', 'evaluation.evaluator:id,name', 'employees:id,name,ac_no'])
            ->withCount('assignments')
            ->orderByDesc('id');

        if (! empty($taskDate)) {
            $tasksQuery->whereDate('task_date', $taskDate);
        }

        if ($employeeId > 0) {
            $tasksQuery->whereHas('employees', fn ($query) => $query->where('employees.id', $employeeId));
        }

        $tasks = $tasksQuery->get();

        $employees = Employee::query()
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('role', 'employee'))
            ->orderBy('name')
            ->get(['id', 'name', 'ac_no']);

        $totalTasks = $tasks->count();
        $evaluatedTasks = $tasks->filter(fn ($task) => $task->evaluation !== null)->count();
        $coverage = $totalTasks > 0 ? round(($evaluatedTasks / $totalTasks) * 100, 2) : 0.0;
        $averageEvaluationScore = $evaluatedTasks > 0
            ? round((float) $tasks->filter(fn ($task) => $task->evaluation !== null)->avg(fn ($task) => (float) $task->evaluation->score), 2)
            : 0.0;

        return view('tasks.admin', [
            'month' => $month,
            'year' => $year,
            'tasks' => $tasks,
            'employees' => $employees,
            'taskDate' => $taskDate,
            'employeeId' => $employeeId,
            'coverage' => $coverage,
            'evaluatedTasks' => $evaluatedTasks,
            'averageEvaluationScore' => $averageEvaluationScore,
            'totalTasks' => $totalTasks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'task_date' => ['required', 'date'],
            'task_end_date' => ['required', 'date', 'after_or_equal:task_date'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year' => ['required', 'integer', 'between:2000,2100'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'integer', 'exists:employees,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $employeeIds = $this->ensureEligibleEmployeeIds($validated['employee_ids']);

        $this->taskManagementService->createTask(
            $validated,
            $employeeIds,
            $request->user()
        );

        return redirect()
            ->route('tasks.admin.index', [
                'month' => (int) $validated['period_month'],
                'year' => (int) $validated['period_year'],
                'task_date' => (string) $validated['task_date'],
            ])
            ->with('success', 'تم إنشاء المهمة وإسنادها بنجاح.');
    }

    public function update(Request $request, EmployeeMonthTask $task): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'task_date' => ['required', 'date'],
            'task_end_date' => ['required', 'date', 'after_or_equal:task_date'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'integer', 'exists:employees,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $employeeIds = $this->ensureEligibleEmployeeIds($validated['employee_ids']);

        $this->taskManagementService->updateTask($task, $validated, $employeeIds);

        return back()->with('success', 'تم تحديث المهمة بنجاح.');
    }

    public function toggle(EmployeeMonthTask $task): RedirectResponse
    {
        $this->taskManagementService->updateTask($task, [
            'is_active' => ! $task->is_active,
        ]);

        return back()->with('success', 'تم تحديث حالة المهمة.');
    }

    public function export(Request $request)
    {
        $period = PayrollPeriod::monthForDate(now());
        $month = (int) $request->input('month', $period['month']);
        $year = (int) $request->input('year', $period['year']);

        $tasks = EmployeeMonthTask::query()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->with(['employees:id,name,ac_no', 'evaluation.evaluator:id,name'])
            ->orderByDesc('id')
            ->get();

        if ($tasks->isEmpty()) {
            return back()->with('error', 'لا توجد مهام لتصديرها في هذا الشهر.');
        }

        $monthLabel = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM_YYYY');
        $fileName = "tasks_evaluations_{$monthLabel}.xlsx";

        return Excel::download(
            new TasksEvaluationsExport($tasks, $month, $year),
            $fileName
        );
    }

    private function ensureEligibleEmployeeIds(array $employeeIds): array
    {
        $requestedIds = collect($employeeIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $eligibleIds = Employee::query()
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('role', 'employee'))
            ->whereIn('id', $requestedIds->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($eligibleIds->count() !== $requestedIds->count()) {
            throw ValidationException::withMessages([
                'employee_ids' => 'يجب إسناد المهمة لموظفين فقط (role = employee).',
            ]);
        }

        return $eligibleIds->all();
    }
}
