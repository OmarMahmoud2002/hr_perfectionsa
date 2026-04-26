<?php

namespace App\Notifications;

use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeEmployeeNotification extends Notification
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employeeName = (string) ($notifiable->name ?? $notifiable->employee?->name ?? 'زميلنا العزيز');

        return (new MailMessage)
            ->subject('مرحبًا بك في نظام الشركة')
            ->view('emails.welcome-employee', [
                'title' => 'مرحبًا بك في نظام الشركة',
                'employeeName' => $employeeName,
                'actionUrl' => route('login'),
                'actionText' => 'تسجيل الدخول الآن',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $employeeName = (string) ($notifiable->name ?? $notifiable->employee?->name ?? 'زميلنا العزيز');

        return [
            'title' => 'مرحبًا بك في النظام',
            'message' => "أهلًا {$employeeName}، تم تفعيل حسابك بنجاح ويمكنك الآن استخدام النظام بالكامل.",
            'url' => route('login'),
            'type' => 'welcome_employee',
        ];
    }
}
