<?php

namespace App\Services\EmployeeOfMonth;

use App\Models\Employee;
use App\Models\EmployeeMonthVote;
use App\Models\User;
use App\Services\Payroll\PayrollPeriod;
use Carbon\Carbon;

class VoteEligibilityService
{
    public function canUserVote(User $user, int $month, int $year, ?Carbon $now = null): array
    {
        $now ??= now();

        if (! $this->isEligibleVoter($user)) {
            return [
                'allowed' => false,
                'reason' => 'ineligible_voter',
            ];
        }

        if (! $this->isVotingWindowOpen($month, $year, $now)) {
            return [
                'allowed' => false,
                'reason' => 'voting_closed',
            ];
        }

        $existingVote = $this->findExistingVote($user->id, $month, $year);
        if ($existingVote) {
            return [
                'allowed' => false,
                'reason' => 'already_voted',
                'vote' => $existingVote,
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'ok',
        ];
    }

    public function canVoteForCandidate(User $voter, Employee $candidate): array
    {
        if (! $this->isEligibleCandidate($candidate)) {
            return [
                'allowed' => false,
                'reason' => 'ineligible_candidate',
            ];
        }

        if ($voter->employee_id !== null && (int) $voter->employee_id === (int) $candidate->id) {
            return [
                'allowed' => false,
                'reason' => 'self_vote_forbidden',
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'ok',
        ];
    }

    public function isEligibleVoter(User $user): bool
    {
        return $user->isEmployee() && $user->employee_id !== null;
    }

    public function isEligibleCandidate(Employee $employee): bool
    {
        if (! $employee->is_active) {
            return false;
        }

        $employee->loadMissing('user');

        return $employee->user !== null && $employee->user->isEmployee();
    }

    public function isVotingWindowOpen(int $month, int $year, ?Carbon $at = null): bool
    {
        $at ??= now();
        $start = PayrollPeriod::startDate($month, $year);
        $end = PayrollPeriod::endDate($month, $year);

        return $at->betweenIncluded($start, $end);
    }

    public function getVotingClosesAt(int $month, int $year): Carbon
    {
        return PayrollPeriod::endDate($month, $year);
    }

    public function secondsRemainingToClose(int $month, int $year, ?Carbon $at = null): int
    {
        $at ??= now();
        $closesAt = $this->getVotingClosesAt($month, $year);

        if ($at->greaterThan($closesAt)) {
            return 0;
        }

        return $at->diffInSeconds($closesAt);
    }

    public function findExistingVote(int $voterUserId, int $month, int $year): ?EmployeeMonthVote
    {
        return EmployeeMonthVote::query()
            ->where('voter_user_id', $voterUserId)
            ->where('vote_month', $month)
            ->where('vote_year', $year)
            ->first();
    }
}
