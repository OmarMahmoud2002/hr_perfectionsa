<?php

namespace App\Notifications;

use App\Models\DailyPerformanceEntry;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyPerformanceReviewedNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(
        private readonly DailyPerformanceEntry $entry,
        private readonly string $reviewerName,
        private readonly int $rating,
        private readonly ?string $comment = null,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تم تقييم أدائك اليومي')
            ->view('emails.generic-event', [
                'title' => 'تقييم الأداء اليومي',
                'eyebrow' => 'الأداء اليومي',
                'heading' => 'تم إضافة تقييم جديد على أدائك اليومي',
                'intro' => "{$this->reviewerName} قيّم سجل أدائك اليومي لهذا اليوم.",
                'details' => array_filter([
                    'التاريخ' => optional($this->entry->work_date)?->format('Y-m-d') ?? '-',
                    'المشروع' => (string) ($this->entry->project_name ?? 'بدون اسم مشروع'),
                    'التقييم' => "{$this->rating} / 5",
                    'الملاحظة' => $this->comment,
                ], fn (?string $value): bool => $value !== null && trim($value) !== ''),
                'actionUrl' => $this->resolveActionUrl(),
                'actionText' => 'فتح الأداء اليومي',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $message = "{$this->reviewerName} قيّم أداءك اليومي بتاريخ " . (optional($this->entry->work_date)?->format('Y-m-d') ?? '-') . " بدرجة {$this->rating}/5";

        if ($this->comment !== null && trim($this->comment) !== '') {
            $message .= '. توجد ملاحظة جديدة على التقييم.';
        }

        return [
            'title' => 'تم تقييم أدائك اليومي',
            'message' => $message,
            'url' => $this->resolveActionUrl(),
            'type' => 'daily_performance_reviewed',
            'entry_id' => (int) $this->entry->id,
            'rating' => $this->rating,
            'reviewer_name' => $this->reviewerName,
            'work_date' => optional($this->entry->work_date)?->format('Y-m-d'),
        ];
    }

    private function resolveActionUrl(): string
    {
        return route('daily-performance.employee.index', [
            'date' => optional($this->entry->work_date)?->format('Y-m-d') ?? now()->toDateString(),
        ]);
    }
}
