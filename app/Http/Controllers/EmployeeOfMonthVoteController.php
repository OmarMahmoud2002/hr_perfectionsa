<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeMonthVoteRequest;
use App\Models\Employee;
use App\Models\EmployeeOfMonthResult;
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
    ) {}

    public function page(): View
    {
        $user = request()->user()->loadMissing('employee');

        $candidatesQuery = Employee::query()
            ->where('is_active', true)
            ->whereHas('user', fn ($q) => $q->where('role', 'employee'))
            ->with('user.profile')
            ->orderBy('name');

        if ($user->employee_id !== null) {
            $candidatesQuery->whereKeyNot((int) $user->employee_id);
        }

        $candidates = $candidatesQuery->get();

        $status = $this->buildStatusPayload($user);
        $currentMonthDate = Carbon::create((int) $status['year'], (int) $status['month'], 1);
        $previousMonthDate = $currentMonthDate->copy()->subMonthNoOverflow();

        $previousMonthTopThree = EmployeeOfMonthResult::query()
            ->with('employee.user.profile')
            ->where('month', (int) $previousMonthDate->month)
            ->where('year', (int) $previousMonthDate->year)
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
            ->take(3)
            ->values();

        $titleHolderEmployeeId = $previousMonthTopThree->first()?->employee_id;

        return view('employee-of-month.vote', [
            'candidates' => $candidates,
            'voteStatus' => $status,
            'previousMonthLabel' => $previousMonthDate->locale('ar')->isoFormat('MMMM YYYY'),
            'previousMonthTopThree' => $previousMonthTopThree,
            'titleHolderEmployeeId' => $titleHolderEmployeeId,
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
