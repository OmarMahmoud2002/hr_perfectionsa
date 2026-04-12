<?php

namespace App\Services\Leave;

use App\Models\LeaveRequest;
use App\Models\LeaveRequestApproval;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveApprovalService
{
    public function __construct(
        private readonly LeaveBalanceService $balanceService,
    ) {}

    public function decide(
        LeaveRequest $leaveRequest,
        User $actor,
        string $decision,
        ?int $approvedDays = null,
        ?string $note = null,
        ?Carbon $decidedAt = null,
        ?array $approvedDates = null,
    ): LeaveRequest {
        $decidedAt ??= now();
        $decision = strtolower(trim($decision));

        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw new LeaveRequestException('invalid_decision', 'قرار الاعتماد غير صالح.');
        }

        return DB::transaction(function () use ($leaveRequest, $actor, $decision, $approvedDays, $note, $decidedAt, $approvedDates) {
            /** @var LeaveRequest $request */
            $request = LeaveRequest::query()
                ->whereKey($leaveRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $request->loadMissing('employee.user');

            if ($request->finalized_at !== null || in_array($request->status, ['approved', 'rejected'], true)) {
                throw new LeaveRequestException('request_already_finalized', 'تم إنهاء هذا الطلب مسبقا.');
            }

            $actorSide = $this->resolveActorSide($request, $actor);

            if ($decision === 'approved') {
                $approvedDays = (int) $request->requested_days;
            } else {
                $approvedDays = null;
            }

            if ($actorSide === 'hr') {
                $request->hr_status = $decision;
                $request->hr_approved_days = $approvedDays;
            } else {
                $request->manager_status = $decision;
            }

            LeaveRequestApproval::query()->create([
                'leave_request_id' => (int) $request->id,
                'actor_user_id' => (int) $actor->id,
                'actor_role' => $actorSide,
                'decision' => $decision,
                'approved_days' => $approvedDays,
                'note' => $note,
                'decided_at' => $decidedAt,
            ]);

            $this->finalizeIfPossible($request, $decidedAt);

            $request->save();

            return $request->fresh(['employee', 'approvals']);
        });
    }

    private function resolveActorSide(LeaveRequest $request, User $actor): string
    {
        $requesterRole = (string) ($request->employee?->user?->role ?? '');
        $isHrLikeRequester = in_array($requesterRole, ['hr', 'admin'], true);

        if (in_array($actor->role, ['hr', 'admin'], true) && ! $isHrLikeRequester && $request->hr_status === 'pending') {
            return 'hr';
        }

        if ($actor->role === 'department_manager') {
            if ((int) ($actor->employee_id ?? 0) === (int) ($request->manager_employee_id ?? 0) && $request->manager_status === 'pending') {
                return 'manager';
            }
        }

        if ($actor->role === 'manager' && $isHrLikeRequester && $request->manager_status === 'pending') {
            return 'manager';
        }

        throw new LeaveRequestException('unauthorized_actor', 'لا تملك صلاحية لاتخاذ قرار على هذا الطلب.');
    }

    private function finalizeIfPossible(LeaveRequest $request, Carbon $decidedAt): void
    {
        if ($request->finalized_at !== null || in_array($request->status, ['approved', 'rejected'], true)) {
            return;
        }

        if ($request->hr_status === 'rejected' || $request->manager_status === 'rejected') {
            $request->status = 'rejected';
            $request->finalized_at = $decidedAt;

            return;
        }

        $hrRequired = $request->hr_status !== 'not_required';
        $managerRequired = $request->manager_status !== 'not_required';

        if (($hrRequired && $request->hr_status !== 'approved') || ($managerRequired && $request->manager_status !== 'approved')) {
            return;
        }

        $finalApprovedDays = (int) ($request->hr_approved_days ?? $request->requested_days ?? 0);

        if ($finalApprovedDays <= 0) {
            throw new LeaveRequestException('invalid_final_approved_days', 'تعذر احتساب عدد الأيام المعتمدة نهائيا.');
        }

        if ($request->final_approved_days !== null && (int) $request->final_approved_days > 0) {
            return;
        }

        $request->final_approved_days = $finalApprovedDays;
        $request->status = 'approved';
        $request->finalized_at = $decidedAt;

        $this->balanceService->consumeDaysForDate($request->employee, $request->start_date->copy(), $finalApprovedDays);
    }

    
}
