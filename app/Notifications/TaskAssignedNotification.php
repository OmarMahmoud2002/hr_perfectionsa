<?php

namespace App\Notifications;

use App\Models\EmployeeMonthTask;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(
        private readonly EmployeeMonthTask $task,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تم إسناد مهمة جديدة لك')
            ->view('emails.task-assigned', [
                'title' => 'مهمة جديدة',
                'taskTitle' => (string) $this->task->title,
                'taskDescription' => (string) ($this->task->description ?? 'بدون وصف'),
                'taskDate' => optional($this->task->task_date)?->format('Y-m-d') ?? '-',
                'taskEndDate' => optional($this->task->task_end_date)?->format('Y-m-d') ?? '-',
                'creatorName' => (string) ($this->task->creator?->name ?? 'الإدارة'),
                'actionUrl' => route('tasks.my.index'),
                'actionText' => 'فتح مهامي',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'تم إسناد مهمة جديدة',
            'message' => 'تم إسناد مهمة جديدة لك: ' . (string) $this->task->title,
            'url' => route('tasks.my.index'),
            'type' => 'task_assigned',
            'task_id' => (int) $this->task->id,
            'task_title' => (string) $this->task->title,
        ];
    }
}
