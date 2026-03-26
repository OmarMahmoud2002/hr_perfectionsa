<?php

namespace App\Http\Controllers;

use App\Models\EmployeeMonthTask;
use App\Services\Payroll\PayrollPeriod;
use Illuminate\Http\Request;
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
                ->with(['evaluation.evaluator:id,name'])
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
}
