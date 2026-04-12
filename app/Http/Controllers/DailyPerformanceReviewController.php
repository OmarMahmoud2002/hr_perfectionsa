<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertDailyPerformanceReviewRequest;
use App\Models\DailyPerformanceEntry;
use App\Models\Employee;
use App\Models\User;
use App\Services\Department\DepartmentScopeService;
use App\Services\DailyPerformance\DailyPerformanceReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DailyPerformanceReviewController extends Controller
{
    public function __construct(
        private readonly DailyPerformanceReviewService $reviewService,
        private readonly DepartmentScopeService $departmentScopeService,
    ) {}

    public function index(Request $request): View
    {
        $dashboard = $this->reviewService->getReviewDashboard(
            $request->user(),
            $request->only(['date', 'employee_id', 'status'])
        );

        $employeesQuery = Employee::query()
            ->active()
            ->whereHas('user', fn ($q) => $q->where('role', 'employee'))
            ->orderBy('name');

        if (! $request->user()->isEvaluatorUser()) {
            $this->departmentScopeService->applyEmployeeScope($employeesQuery, $request->user());
        }

        $employees = $employeesQuery->get(['id', 'name']);

        return view('daily-performance.review', [
            'cards' => $dashboard['cards'],
            'filters' => $dashboard['filters'],
            'stats' => $dashboard['stats'],
            'employees' => $employees,
        ]);
    }

    public function upsert(UpsertDailyPerformanceReviewRequest $request, DailyPerformanceEntry $entry): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->reviewService->upsertReview(
                $request->user(),
                $entry,
                (int) $validated['rating'],
                $validated['comment'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم حفظ تقييم الأداء اليومي بنجاح.');
    }
}
