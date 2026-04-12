<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeMonthVoteRequest;
use App\Models\Employee;
use App\Models\EmployeeOfMonthPublication;
use App\Models\EmployeeOfMonthResult;
use App\Models\User;
use App\Services\EmployeeOfMonth\BestManagerOfMonthService;
use App\Services\EmployeeOfMonth\EmployeeOfMonthScoringService;
use App\Services\EmployeeOfMonth\EmployeeOfMonthVoteException;
use App\Services\EmployeeOfMonth\VoteEligibilityService;
use App\Services\EmployeeOfMonth\VoteSubmissionService;
use App\Services\Payroll\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class EmployeeOfMonthVoteController extends Controller
{
    public function __construct(
        private readonly VoteEligibilityService $eligibilityService,
        private readonly VoteSubmissionService $submissionService,
        private readonly BestManagerOfMonthService $bestManagerService,
    ) {}

    public function page(): View
    {
        $user = request()->user()->loadMissing('employee');

        $candidatesQuery = Employee::query()
            ->where('is_active', true)
            ->where('is_department_manager', false)
            ->whereHas('user', fn ($q) => $q->whereIn('role', User::employeeOfMonthCandidateRoles()))
            ->with(['user.profile', 'department', 'jobTitleRef'])
            ->orderBy('name');

        if ($user->isDepartmentManager()) {
            $departmentId = $user->employee?->department_id;

            if ($departmentId === null) {
                $candidatesQuery->whereRaw('1 = 0');
            } else {
                $candidatesQuery->where('department_id', $departmentId);
            }
        }

        if ($user->employee_id !== null) {
            $candidatesQuery->whereKeyNot((int) $user->employee_id);
        }

        $candidates = $candidatesQuery->get();

        $status = $this->buildStatusPayload($user);
        $currentMonthDate = Carbon::create((int) $status['year'], (int) $status['month'], 1);
        $previousMonthDate = $currentMonthDate->copy()->subMonthNoOverflow();

        $previousMonthPublished = EmployeeOfMonthPublication::query()
            ->where('month', (int) $previousMonthDate->month)
            ->where('year', (int) $previousMonthDate->year)
            ->exists();

        $previousMonthTopThree = collect();

        if ($previousMonthPublished) {
            $previousMonthTopThree = EmployeeOfMonthResult::query()
                ->with(['employee.user.profile', 'employee.department', 'employee.jobTitleRef'])
                ->where('month', (int) $previousMonthDate->month)
                ->where('year', (int) $previousMonthDate->year)
                ->where('final_score', '>=', EmployeeOfMonthScoringService::MIN_RANKING_SCORE)
                ->get()
                ->sort(function (EmployeeOfMonthResult $a, EmployeeOfMonthResult $b) {
                    $finalCompare = ((float) $b->final_score) <=> ((float) $a->final_score);
                    if ($finalCompare !== 0) {
                        return $finalCompare;
                    }

                    $taskA = (float) data_get($a->breakdown, 'task_points', data_get($a->breakdown, 'task_score', 0));
                    $taskB = (float) data_get($b->breakdown, 'task_points', data_get($b->breakdown, 'task_score', 0));

                    return $taskB <=> $taskA;
                })
                ->take(EmployeeOfMonthScoringService::WINNERS_COUNT)
                ->values();
        }

        $titleHolderEmployeeId = $previousMonthTopThree->first()?->employee_id;
        $previousMonthBestManager = $previousMonthPublished
            ? $this->bestManagerService->resolveForMonth((int) $previousMonthDate->month, (int) $previousMonthDate->year)
            : null;

        return view('employee-of-month.vote', [
            'candidates' => $candidates,
            'voteStatus' => $status,
            'previousMonthLabel' => $previousMonthDate->locale('ar')->isoFormat('MMMM YYYY'),
            'previousMonthTopThree' => $previousMonthTopThree,
            'titleHolderEmployeeId' => $titleHolderEmployeeId,
            'previousMonthPublished' => $previousMonthPublished,
            'previousMonthBestManager' => $previousMonthBestManager,
        ]);
    }

    public function status(): JsonResponse
    {
        return response()->json($this->buildStatusPayload(request()->user()));
    }

    public function store(StoreEmployeeMonthVoteRequest $request): JsonResponse
    {
        $user = $request->user();
        $now = now();
        $period = PayrollPeriod::monthForDate($now);
        $month = (int) $period['month'];
        $year = (int) $period['year'];

        $candidate = Employee::query()->findOrFail((int) $request->integer('voted_employee_id'));

        try {
            $result = $this->submissionService->submit($user, $candidate, $month, $year, $now);
        } catch (EmployeeOfMonthVoteException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'reason' => $e->reason(),
            ], 422);
        }

        $vote = $result['vote'];

        return response()->json([
            'status' => $result['status'],
            'has_voted' => true,
            'voted_employee_id' => $vote->voted_employee_id,
            'vote_id' => $vote->id,
            'voting_closes_at' => $this->eligibilityService->getVotingClosesAt($month, $year)->toIso8601String(),
            'seconds_remaining_to_close' => $this->eligibilityService->secondsRemainingToClose($month, $year, $now),
        ], $result['status'] === 'created' ? 201 : 200);
    }

    private function buildStatusPayload($user): array
    {
        $now = now();
        $period = PayrollPeriod::monthForDate($now);
        $month = (int) $period['month'];
        $year = (int) $period['year'];

        $eligibility = $this->eligibilityService->canUserVote($user, $month, $year, $now);
        $existingVote = $this->eligibilityService->findExistingVote($user->id, $month, $year);

        return [
            'month' => $month,
            'year' => $year,
            'has_voted' => $existingVote !== null,
            'can_vote' => (bool) $eligibility['allowed'],
            'reason' => $eligibility['reason'],
            'voted_employee_id' => $existingVote?->voted_employee_id,
            'voting_closes_at' => $this->eligibilityService->getVotingClosesAt($month, $year)->toIso8601String(),
            'seconds_remaining_to_close' => $this->eligibilityService->secondsRemainingToClose($month, $year, $now),
        ];
    }
}
