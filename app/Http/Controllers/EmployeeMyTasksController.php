<?php

namespace App\Http\Controllers;

use App\Enums\TaskAssignmentStatus;
use App\Models\EmployeeMonthTask;
use App\Models\EmployeeMonthTaskAssignment;
use App\Services\Payroll\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeMyTasksController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing('employee');
        $period = PayrollPeriod::monthForDate(now());
        $month = (int) $request->input('month', $period['month']);
        $year = (int) $request->input('year', $period['year']);

        $employee = $user->employee;

        $tasks = collect();
        if ($employee) {
            $tasks = EmployeeMonthTask::query()
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->whereHas('assignments', fn ($q) => $q->where('employee_id', $employee->id))
                ->with([
                    'evaluation.evaluator:id,name',
                    'employees:id,name',
                    'attachments',
                    'links',
                    'assignments' => fn ($query) => $query
                        ->where('employee_id', $employee->id)
                        ->select(['id', 'task_id', 'employee_id', 'status']),
                ])
                ->orderByDesc('id')
                ->get();
        }

        return view('tasks.my-tasks', [
            'month' => $month,
            'year' => $year,
            'employee' => $employee,
            'tasks' => $tasks,
        ]);
    }

    public function updateStatus(Request $request, EmployeeMonthTask $task): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(TaskAssignmentStatus::values())],
        ]);

        $employee = $request->user()->loadMissing('employee')->employee;
        if (! $employee) {
            return back()->with('error', 'حسابك غير مرتبط بموظف.');
        }

        $assignment = EmployeeMonthTaskAssignment::query()
            ->where('task_id', $task->id)
            ->where('employee_id', $employee->id)
            ->first();

        if (! $assignment) {
            return back()->with('error', 'لا يمكنك تعديل حالة مهمة غير مسندة إليك.');
        }

        $assignment->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'تم تحديث حالة المهمة بنجاح.');
    }
}
