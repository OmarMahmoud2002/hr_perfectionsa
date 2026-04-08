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

        if (! in_array($decision, ['approved', 'partially_approved', 'rejected'], true)) {
            throw new LeaveRequestException('invalid_decision', 'قرار الاعتماد غير صالح.');
        }

        return DB::transaction(function () use ($leaveRequest, $actor, $decision, $approvedDays, $note, $decidedAt, $approvedDates) {
            /** @var LeaveRequest $request */
            $request = LeaveRequest::query()
                ->whereKey($leaveRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->finalized_at !== null || in_array($request->status, ['approved', 'partially_approved', 'rejected'], true)) {
                throw new LeaveRequestException('request_already_finalized', 'تم إنهاء هذا الطلب مسبقا.');
            }

            $actorSide = $this->resolveActorSide($request, $actor);

            if ($actorSide === 'manager' && $decision === 'partially_approved') {
                throw new LeaveRequestException('partial_approval_hr_only', 'الموافقة الجزئية متاحة للـ HR فقط.');
            }

            if ($actorSide === 'hr') {
                if ($decision === 'partially_approved') {
                    $normalizedApprovedDates = $this->normalizeApprovedDates($approvedDates, $request);

                    if (! empty($normalizedApprovedDates)) {
                        $approvedDays = count($normalizedApprovedDates);
                        $note = $this->appendApprovedDatesToNote($note, $normalizedApprovedDates);
                    }

                    if ($approvedDays === null || $approvedDays <= 0) {
                        throw new LeaveRequestException('invalid_approved_days', 'عدد الأيام الجزئية غير صالح.');
                    }

                    if ($approvedDays >= (int) $request->requested_days) {
                        throw new LeaveRequestException('partial_days_must_be_less_than_requested', 'الموافقة الجزئية يجب أن تكون أقل من الأيام المطلوبة.');
                    }
                } elseif ($decision === 'approved') {
                    $approvedDays = (int) $request->requested_days;
                } else {
                    $approvedDays = null;
                }

                $request->hr_status = $decision;
                $request->hr_approved_days = $approvedDays;
            } else {
                $request->manager_status = $decision;
            }

            LeaveRequestApproval::query()->create([
                'leave_request_id' => (int) $request->id,
                'actor_user_id' => (int) $actor->id,
                'actor_role' => $actorSide === 'hr' ? 'hr' : 'department_manager',
                'decision' => $decision,
                'approved_days' => $actorSide === 'hr' ? $approvedDays : null,
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
        if (in_array($actor->role, ['hr', 'admin', 'manager'], true)) {
            return 'hr';
        }

        if ($actor->role !== 'department_manager') {
            throw new LeaveRequestException('unauthorized_actor', 'لا تملك صلاحية لاتخاذ قرار على هذا الطلب.');
        }

        if ($request->manager_status === 'not_required' || $request->manager_employee_id === null) {
            throw new LeaveRequestException('manager_decision_not_required', 'هذا الطلب لا يحتاج قرار مدير قسم.');
        }

        if ((int) ($actor->employee_id ?? 0) !== (int) $request->manager_employee_id) {
            throw new LeaveRequestException('not_request_department_manager', 'لا يمكنك اتخاذ قرار على طلب ليس تابعا لقسمك.');
        }

        return 'manager';
    }

    private function finalizeIfPossible(LeaveRequest $request, Carbon $decidedAt): void
    {
        if ($request->hr_status === 'rejected' || $request->manager_status === 'rejected') {
            $request->status = 'rejected';
            $request->finalized_at = $decidedAt;

            return;
        }

        $managerRequired = $request->manager_status !== 'not_required';

        if ($managerRequired && $request->manager_status !== 'approved') {
            return;
        }

        if (! in_array($request->hr_status, ['approved', 'partially_approved'], true)) {
            return;
        }

        $finalApprovedDays = (int) ($request->hr_approved_days ?? 0);

        if ($finalApprovedDays <= 0) {
            throw new LeaveRequestException('invalid_final_approved_days', 'تعذر احتساب عدد الأيام المعتمدة نهائيا.');
        }

        $request->final_approved_days = $finalApprovedDays;
        $request->status = $finalApprovedDays < (int) $request->requested_days ? 'partially_approved' : 'approved';
        $request->finalized_at = $decidedAt;

        $this->balanceService->consumeDaysForDate($request->employee, $request->start_date->copy(), $finalApprovedDays);
    }

    private function normalizeApprovedDates(?array $approvedDates, LeaveRequest $request): array
    {
        if (! is_array($approvedDates)) {
            return [];
        }

        $start = $request->start_date?->format('Y-m-d');
        $end = $request->end_date?->format('Y-m-d');

        return collect($approvedDates)
            ->filter(fn ($date) => is_string($date) && $date !== '')
            ->map(function (string $date): string {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->filter(function (string $date) use ($start, $end): bool {
                if ($start === null || $end === null) {
                    return true;
                }

                return $date >= $start && $date <= $end;
            })
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function appendApprovedDatesToNote(?string $note, array $approvedDates): string
    {
        $datesLine = 'تواريخ الاعتماد الجزئي: '.implode('، ', $approvedDates);

        if ($note === null || trim($note) === '') {
            return $datesLine;
        }

        return trim($note)."\n".$datesLine;
    }
}
