<?php

namespace App\Services\EmployeeOfMonth;

use App\Models\Employee;
use App\Models\EmployeeMonthVote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class VoteSubmissionService
{
    public function __construct(
        private readonly VoteEligibilityService $eligibilityService
    ) {}

    public function submit(User $voter, Employee $candidate, int $month, int $year, ?Carbon $now = null): array
    {
        $now ??= now();

        return DB::transaction(function () use ($voter, $candidate, $month, $year, $now) {
            // Lock voter row to reduce race windows for concurrent submissions.
            $lockedVoter = User::query()->whereKey($voter->id)->lockForUpdate()->firstOrFail();

            $userEligibility = $this->eligibilityService->canUserVote($lockedVoter, $month, $year, $now);
            if (! $userEligibility['allowed']) {
                if ($userEligibility['reason'] === 'already_voted') {
                    return [
                        'status' => 'already_voted',
                        'vote' => $userEligibility['vote'],
                    ];
                }

                throw $this->buildVoteException($userEligibility['reason']);
            }

            $candidateEligibility = $this->eligibilityService->canVoteForCandidate($lockedVoter, $candidate);
            if (! $candidateEligibility['allowed']) {
                throw $this->buildVoteException($candidateEligibility['reason']);
            }

            $existingVote = EmployeeMonthVote::query()
                ->where('voter_user_id', $lockedVoter->id)
                ->where('vote_month', $month)
                ->where('vote_year', $year)
                ->lockForUpdate()
                ->first();

            if ($existingVote) {
                return [
                    'status' => 'already_voted',
                    'vote' => $existingVote,
                ];
            }

            try {
                $vote = EmployeeMonthVote::query()->create([
                    'voter_user_id' => $lockedVoter->id,
                    'voted_employee_id' => $candidate->id,
                    'vote_month' => $month,
                    'vote_year' => $year,
                ]);

                return [
                    'status' => 'created',
                    'vote' => $vote,
                ];
            } catch (QueryException $e) {
                if (! $this->isDuplicateKeyException($e)) {
                    throw $e;
                }

                $alreadyVote = EmployeeMonthVote::query()
                    ->where('voter_user_id', $lockedVoter->id)
                    ->where('vote_month', $month)
                    ->where('vote_year', $year)
                    ->first();

                if (! $alreadyVote) {
                    throw $e;
                }

                return [
                    'status' => 'already_voted',
                    'vote' => $alreadyVote,
                ];
            }
        });
    }

    private function isDuplicateKeyException(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (string) ($e->errorInfo[1] ?? '');

        return $sqlState === '23000' || $sqlState === '23505' || $driverCode === '1062';
    }

    private function buildVoteException(string $reason): EmployeeOfMonthVoteException
    {
        return match ($reason) {
            'ineligible_voter' => new EmployeeOfMonthVoteException($reason, 'غير مسموح لك بالتصويت.'),
            'voting_closed' => new EmployeeOfMonthVoteException($reason, 'تم إغلاق التصويت لهذا الشهر.'),
            'ineligible_candidate' => new EmployeeOfMonthVoteException($reason, 'الموظف المختار غير مؤهل للتصويت.'),
            'self_vote_forbidden' => new EmployeeOfMonthVoteException($reason, 'لا يمكن التصويت لنفسك.'),
            'voter_without_department' => new EmployeeOfMonthVoteException($reason, 'لا يمكنك التصويت قبل ربطك بقسم.'),
            'candidate_outside_department' => new EmployeeOfMonthVoteException($reason, 'يمكنك التصويت لموظفي قسمك فقط.'),
            default => new EmployeeOfMonthVoteException($reason, 'تعذر إتمام التصويت.'),
        };
    }
}
