<?php

namespace App\Notifications;

use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeOfMonthPublishedNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(
        private readonly int $month,
        private readonly int $year,
        private readonly ?string $winnerName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $monthLabel = str_pad((string) $this->month, 2, '0', STR_PAD_LEFT) . '/' . $this->year;
        $winnerLine = $this->winnerName
            ? 'أفضل موظف لهذا الشهر: ' . $this->winnerName
            : 'تم نشر نتائج أفضل موظف لهذا الشهر.';

        return (new MailMessage)
            ->subject('تم اعتماد أفضل موظف في الشهر')
            ->line('تم اعتماد ونشر نتائج أفضل موظف للشهر ' . $monthLabel . '.')
            ->line($winnerLine)
            ->action('عرض النتائج', route('employee-of-month.vote.page'));
    }

    public function toArray(object $notifiable): array
    {
        $monthLabel = str_pad((string) $this->month, 2, '0', STR_PAD_LEFT) . '/' . $this->year;
        $message = $this->winnerName
            ? 'تم اعتماد نتائج أفضل موظف للشهر ' . $monthLabel . '. الفائز: ' . $this->winnerName
            : 'تم اعتماد نتائج أفضل موظف للشهر ' . $monthLabel . '.';

        return [
            'title' => 'اعتماد أفضل موظف في الشهر',
            'message' => $message,
            'url' => route('employee-of-month.vote.page'),
            'type' => 'employee_of_month_published',
            'month' => $this->month,
            'year' => $this->year,
            'winner_name' => $this->winnerName,
        ];
    }
}
