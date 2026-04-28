<?php

namespace App\Notifications\Concerns;

trait ResolvesNotificationChannels
{
    protected function resolveNotificationChannels(object $notifiable): array
    {
        $channels = ['database'];
        $email = $notifiable->email ?? null;

        if (is_string($email) && trim($email) !== '' && $this->shouldUseMailChannel()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    private function shouldUseMailChannel(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        $queue = (string) config('queue.default');
        $mailer = (string) config('mail.default');

        if ($queue === 'sync' && in_array($mailer, ['smtp', 'sendmail'], true)) {
            return false;
        }

        return true;
    }
}
