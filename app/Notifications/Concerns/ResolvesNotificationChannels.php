<?php

namespace App\Notifications\Concerns;

trait ResolvesNotificationChannels
{
    protected function resolveNotificationChannels(object $notifiable): array
    {
        $channels = ['database'];
        $email = $notifiable->email ?? null;

        if (is_string($email) && trim($email) !== '') {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
