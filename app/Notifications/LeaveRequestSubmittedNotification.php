<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmittedNotification extends Notification
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(
        private readonly LeaveRequest $leaveRequest,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employeeName = (string) ($this->leaveRequest->employee?->name ?? 'موظف');
        $approvalsUrl = route('leave.approvals.index');

        return (new MailMessage)
            ->subject('طلب إجازة جديد يحتاج المراجعة')
            ->view('emails.leave-request-submitted', [
                'title' => 'طلب إجازة جديد',
                'employeeName' => $employeeName,
                'startDate' => optional($this->leaveRequest->start_date)?->format('Y-m-d') ?? '-',
                'endDate' => optional($this->leaveRequest->end_date)?->format('Y-m-d') ?? '-',
                'requestedDays' => (int) $this->leaveRequest->requested_days,
                'reason' => (string) ($this->leaveRequest->reason ?? 'لا يوجد سبب مذكور.'),
                'actionUrl' => $approvalsUrl,
                'actionText' => 'فتح صفحة اعتماد الإجازات',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $employeeName = (string) ($this->leaveRequest->employee?->name ?? 'موظف');

        return [
            'title' => 'طلب إجازة جديد',
            'message' => "تم تقديم طلب إجازة جديد بواسطة {$employeeName}.",
            'url' => route('leave.approvals.index'),
            'type' => 'leave_request_submitted',
            'leave_request_id' => (int) $this->leaveRequest->id,
            'employee_name' => $employeeName,
        ];
    }
}
