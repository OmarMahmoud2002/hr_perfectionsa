<?php

namespace App\Notifications;

use App\Models\Employee;
use App\Models\EmployeeMonthTask;
use App\Models\User;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCompletedNotification extends Notification
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(
        private readonly EmployeeMonthTask $task,
        private readonly Employee $employee,
        private readonly string $actorName,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تم إنجاز مهمة من المهام المسندة')
            ->view('emails.generic-event', [
                'title' => 'إنجاز مهمة',
                'eyebrow' => 'تحديث المهام',
                'heading' => 'تم تحويل المهمة إلى Done',
                'intro' => "{$this->actorName} أنهى المهمة المسندة له، ويمكنك مراجعة حالتها الآن.",
                'details' => [
                    'الموظف' => (string) $this->employee->name,
                    'عنوان المهمة' => (string) $this->task->title,
                    'تاريخ المهمة' => optional($this->task->task_date)?->format('Y-m-d') ?? '-',
                    'تاريخ النهاية' => optional($this->task->task_end_date)?->format('Y-m-d') ?? '-',
                ],
                'actionUrl' => $this->resolveActionUrl($notifiable),
                'actionText' => 'فتح المهام',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'تم إنجاز مهمة',
            'message' => "{$this->actorName} غيّر حالة المهمة إلى Done: {$this->task->title}",
            'url' => $this->resolveActionUrl($notifiable),
            'type' => 'task_completed',
            'task_id' => (int) $this->task->id,
            'task_title' => (string) $this->task->title,
            'employee_id' => (int) $this->employee->id,
            'employee_name' => (string) $this->employee->name,
        ];
    }

    private function resolveActionUrl(object $notifiable): string
    {
        $params = [
            'month' => (int) $this->task->period_month,
            'year' => (int) $this->task->period_year,
        ];

        if ($this->task->task_date !== null) {
            $params['task_date'] = $this->task->task_date->format('Y-m-d');
        }

        if ($notifiable instanceof User && $notifiable->isEvaluatorUser()) {
            return route('tasks.evaluator.index', $params);
        }

        if ($notifiable instanceof User && in_array($notifiable->role, ['admin', 'hr', 'manager', 'department_manager'], true)) {
            return route('tasks.admin.index', $params + ['employee_id' => (int) $this->employee->id]);
        }

        return route('notifications.index');
    }
}
