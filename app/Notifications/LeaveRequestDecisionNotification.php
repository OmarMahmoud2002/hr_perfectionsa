<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestDecisionNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(
        private readonly LeaveRequest $leaveRequest,
        private readonly string $actorName,
        private readonly string $decision,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $decisionKey = strtolower($this->decision);
        $decisionLabel = $decisionKey === 'approved' ? 'تمت الموافقة' : 'تم الرفض';
        $statusLabel = match (strtolower((string) ($this->leaveRequest->status ?? 'pending'))) {
            'approved' => 'تمت الموافقة',
            'rejected' => 'تم الرفض',
            'pending' => 'قيد المراجعة',
            'cancelled' => 'ملغي',
            default => (string) ($this->leaveRequest->status ?? 'قيد المراجعة'),
        };

        return (new MailMessage)
            ->subject('تحديث على طلب الإجازة الخاص بك')
            ->view('emails.leave-request-decision', [
                'title' => 'تحديث طلب الإجازة',
                'decisionLabel' => $decisionLabel,
                'decisionKey' => $decisionKey,
                'statusLabel' => $statusLabel,
                'actorName' => $this->actorName,
                'startDate' => optional($this->leaveRequest->start_date)?->format('Y-m-d') ?? '-',
                'endDate' => optional($this->leaveRequest->end_date)?->format('Y-m-d') ?? '-',
                'requestedDays' => (int) $this->leaveRequest->requested_days,
                'finalApprovedDays' => (int) ($this->leaveRequest->final_approved_days ?? 0),
                'actionUrl' => route('leave.requests.index'),
                'actionText' => 'عرض طلبات الإجازة',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $decisionLabel = strtolower($this->decision) === 'approved' ? 'تمت الموافقة' : 'تم الرفض';

        return [
            'title' => 'تحديث طلب الإجازة',
            'message' => "{$decisionLabel} على طلب إجازتك بواسطة {$this->actorName}.",
            'url' => route('leave.requests.index'),
            'type' => 'leave_request_decision',
            'leave_request_id' => (int) $this->leaveRequest->id,
            'decision' => strtolower($this->decision),
        ];
    }
}
