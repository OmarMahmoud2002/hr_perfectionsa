<?php

namespace App\Http\Controllers;

use App\Exports\EvaluatorTasksExport;
use App\Models\EmployeeMonthTask;
use App\Services\EmployeeOfMonth\TaskEvaluationService;
use App\Services\Payroll\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class TaskEvaluationController extends Controller
{
    public function __construct(
        private readonly TaskEvaluationService $taskEvaluationService,
    ) {}

    public function index(Request $request): View
    {
        $period = PayrollPeriod::monthForDate(now());
        $month = (int) $request->input('month', $period['month']);
        $year = (int) $request->input('year', $period['year']);
        $taskDate = $request->input('task_date');

        $tasks = $this->taskEvaluationService->getTasksForEvaluator(
            $request->user(),
            $month,
            $year,
            $taskDate,
        );

        return view('tasks.evaluator', [
            'month' => $month,
            'year' => $year,
            'tasks' => $tasks,
            'taskDate' => $taskDate,
        ]);
    }

    public function upsert(Request $request, EmployeeMonthTask $task): RedirectResponse
    {
        $validated = $request->validate([
            'score' => ['required', 'integer', 'between:1,10'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->taskEvaluationService->upsertEvaluation(
                $request->user(),
                $task,
                (int) $validated['score'],
                $validated['note'] ?? null
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم حفظ تقييم المهمة بنجاح.');
    }

    public function export(Request $request)
    {
        $period = PayrollPeriod::monthForDate(now());
        $month = (int) $request->input('month', $period['month']);
        $year = (int) $request->input('year', $period['year']);
        $taskDate = $request->input('task_date');

        $tasks = $this->taskEvaluationService->getTasksForEvaluator(
            $request->user(),
            $month,
            $year,
            $taskDate,
        );

        if ($tasks->isEmpty()) {
            return back()->with('error', 'لا توجد مهام لتصديرها.');
        }

        $monthLabel = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM_YYYY');
        $fileName = "my_evaluations_{$monthLabel}.xlsx";

        return Excel::download(new EvaluatorTasksExport($tasks), $fileName);
    }
}
