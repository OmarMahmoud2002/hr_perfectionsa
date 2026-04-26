<?php

namespace App\Notifications;

use App\Models\EmployeeMonthTask;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskEvaluationSubmittedNotification extends Notification
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(
        private readonly EmployeeMonthTask $task,
        private readonly string $evaluatorName,
        private readonly float $score,
        private readonly ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تم تقييم إحدى مهامك')
            ->view('emails.generic-event', [
                'title' => 'تقييم مهمة',
                'eyebrow' => 'تقييم المهام',
                'heading' => 'تم تقييم المهمة المسندة لك',
                'intro' => "{$this->evaluatorName} أضاف تقييمًا جديدًا على المهمة الخاصة بك.",
                'details' => array_filter([
                    'عنوان المهمة' => (string) $this->task->title,
                    'الدرجة' => number_format($this->score, 1) . ' / 10',
                    'الملاحظة' => $this->note,
                ], fn (?string $value): bool => $value !== null && trim($value) !== ''),
                'actionUrl' => $this->resolveActionUrl(),
                'actionText' => 'فتح مهامي',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $message = "{$this->evaluatorName} قيّم المهمة \"{$this->task->title}\" بدرجة " . number_format($this->score, 1) . '/10';

        if ($this->note !== null && trim($this->note) !== '') {
            $message .= ' مع ملاحظة جديدة.';
        }

        return [
            'title' => 'تم تقييم مهمة لك',
            'message' => $message,
            'url' => $this->resolveActionUrl(),
            'type' => 'task_evaluated',
            'task_id' => (int) $this->task->id,
            'task_title' => (string) $this->task->title,
            'score' => $this->score,
            'evaluator_name' => $this->evaluatorName,
        ];
    }

    private function resolveActionUrl(): string
    {
        return route('tasks.my.index', [
            'month' => (int) $this->task->period_month,
            'year' => (int) $this->task->period_year,
        ]);
    }
}
