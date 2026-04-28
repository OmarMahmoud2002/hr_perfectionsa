<?php

namespace App\Notifications;

use App\Notifications\Concerns\ResolvesNotificationChannels;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceMonthImportedNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(
        private readonly int $month,
        private readonly int $year,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $monthLabel = $this->monthLabel();

        return (new MailMessage)
            ->subject('تم رفع حضور الشهر وإتاحته للاطلاع')
            ->view('emails.generic-event', [
                'title' => 'رفع حضور الشهر',
                'eyebrow' => 'الحضور والانصراف',
                'heading' => 'تم رفع ملف حضور الشهر',
                'intro' => "أصبح ملف حضور شهر {$monthLabel} متاحًا داخل النظام ويمكنك الدخول للاطلاع على بياناتك.",
                'details' => [
                    'الشهر' => $monthLabel,
                ],
                'actionUrl' => $this->resolveActionUrl(),
                'actionText' => 'فتح صفحة حسابي',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $monthLabel = $this->monthLabel();

        return [
            'title' => 'تم رفع ملف حضور الشهر',
            'message' => "تم رفع ملف حضور شهر {$monthLabel}. ادخل الآن للاطلاع على بيانات الحضور الخاصة بك.",
            'url' => $this->resolveActionUrl(),
            'type' => 'attendance_month_imported',
            'month' => $this->month,
            'year' => $this->year,
            'month_label' => $monthLabel,
        ];
    }

    private function resolveActionUrl(): string
    {
        return route('account.my', [
            'month' => $this->month,
            'year' => $this->year,
        ]);
    }

    private function monthLabel(): string
    {
        return Carbon::create($this->year, $this->month, 1)->locale('ar')->isoFormat('MMMM YYYY');
    }
}
