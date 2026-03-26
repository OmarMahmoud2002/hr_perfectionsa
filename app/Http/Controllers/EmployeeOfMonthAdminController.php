<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertEmployeeMonthAdminScoreRequest;
use App\Models\EmployeeMonthAdminScore;
use App\Models\EmployeeOfMonthResult;
use App\Exports\EmployeeOfMonthRankingExport;
use App\Services\EmployeeOfMonth\EmployeeOfMonthFinalizationService;
use App\Services\EmployeeOfMonth\EmployeeOfMonthMetricsService;
use App\Services\EmployeeOfMonth\EmployeeOfMonthScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeOfMonthAdminController extends Controller
{
    public function __construct(
        private readonly EmployeeOfMonthMetricsService $metricsService,
        private readonly EmployeeOfMonthScoringService $scoringService,
        private readonly EmployeeOfMonthFinalizationService $finalizationService,
    ) {}

    public function index(Request $request): View
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $metrics = $this->metricsService->getMonthlyMetrics($month, $year);
        $scoring = $this->scoringService->calculateForMonth($month, $year, $metrics);

        $voteRanking = collect($metrics['rows'])
            ->sortByDesc('votes_count')
            ->values();

        $performanceRows = collect($metrics['rows']);
        $topWorkHours = $performanceRows->sortByDesc('work_minutes')->first();
        $topPunctuality = $performanceRows->sortBy('late_minutes')->first();
        $topOvertime = $performanceRows->sortByDesc('overtime_minutes')->first();

        $taskCoverage = (float) ($metrics['task_period_totals']['coverage_ratio'] ?? 0.0);
        $explainRows = collect($scoring['scored_rows'])
            ->map(function (array $row) {
                $breakdown = $row['breakdown'];

                return [
                    'employee' => $row['employee'],
                    'final_score' => (float) $row['final_score'],
                    'task_score' => (float) ($breakdown['task_score'] ?? 0),
                    'vote_score' => (float) ($breakdown['vote_score'] ?? 0),
                    'work_hours_score' => (float) ($breakdown['work_hours_score'] ?? 0),
                    'punctuality_score' => (float) ($breakdown['punctuality_score'] ?? 0),
                    'raw_inputs' => $breakdown['raw_inputs'] ?? [],
                ];
            })
            ->values();

        $historyTopWinners = EmployeeOfMonthResult::query()
            ->with('employee.user.profile')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('final_score')
            ->get()
            ->groupBy(fn ($row) => $row->year . '-' . str_pad((string) $row->month, 2, '0', STR_PAD_LEFT))
            ->map(fn ($group) => $group->first())
            ->values();

        $historyForSelectedMonth = EmployeeOfMonthResult::query()
            ->with('employee.user.profile')
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('final_score')
            ->get();

        return view('employee-of-month.admin', [
            'month' => $month,
            'year' => $year,
            'metrics' => $metrics,
            'voteRanking' => $voteRanking,
            'topWorkHours' => $topWorkHours,
            'topPunctuality' => $topPunctuality,
            'topOvertime' => $topOvertime,
            'taskCoverage' => $taskCoverage,
            'explainRows' => $explainRows,
            'scoring' => $scoring,
            'historyTopWinners' => $historyTopWinners,
            'historyForSelectedMonth' => $historyForSelectedMonth,
        ]);
    }

    public function upsertScore(UpsertEmployeeMonthAdminScoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        EmployeeMonthAdminScore::query()->updateOrCreate(
            [
                'employee_id' => (int) $validated['employee_id'],
                'month' => (int) $validated['month'],
                'year' => (int) $validated['year'],
            ],
            [
                'score' => (int) $validated['score'],
                'note' => $validated['note'] ?? null,
                'created_by' => $request->user()->id,
            ]
        );

        return redirect()
            ->route('employee-of-month.admin.index', [
                'month' => (int) $validated['month'],
                'year' => (int) $validated['year'],
            ])
            ->with('success', 'تم حفظ تقييم الإدارة بنجاح.');
    }

    public function finalize(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ]);

        $result = $this->finalizationService->finalizeMonth((int) $validated['month'], (int) $validated['year']);

        return redirect()
            ->route('employee-of-month.admin.index', [
                'month' => (int) $validated['month'],
                'year' => (int) $validated['year'],
            ])
            ->with('success', 'تم اعتماد النتائج وحفظ ' . $result['rows_count'] . ' سجل في History.');
    }

    public function exportRanking(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $metrics = $this->metricsService->getMonthlyMetrics($month, $year);
        $scoring = $this->scoringService->calculateForMonth($month, $year, $metrics);

        if (collect($scoring['scored_rows'])->isEmpty()) {
            return back()->with('error', 'لا توجد بيانات كافية للتصدير.');
        }

        $monthLabel = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM_YYYY');
        $fileName = "employee_of_month_{$monthLabel}.xlsx";

        return Excel::download(
            new EmployeeOfMonthRankingExport($scoring, $month, $year),
            $fileName
        );
    }
}
