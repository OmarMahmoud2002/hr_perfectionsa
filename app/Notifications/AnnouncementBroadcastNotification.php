<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AnnouncementBroadcastNotification extends Notification
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(
        private readonly Announcement $announcement,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $embeddedImagePath = null;

        if (is_string($this->announcement->image_path) && $this->announcement->image_path !== '') {
            $candidate = storage_path('app/public/' . ltrim($this->announcement->image_path, '/'));
            if (is_file($candidate)) {
                $embeddedImagePath = $candidate;
            }
        }

        return (new MailMessage)
            ->subject('Perfection System | ' . $this->announcement->title)
            ->view('emails.announcement-broadcast', [
                'title' => $this->announcement->title,
                'messageBody' => (string) $this->announcement->message,
                'senderName' => (string) ($this->announcement->sender?->name ?? 'الإدارة'),
                'linkUrl' => $this->announcement->link_url,
                'imageUrl' => $this->announcement->image_path ? url('/storage/' . ltrim($this->announcement->image_path, '/')) : null,
                'embeddedImagePath' => $embeddedImagePath,
                'actionUrl' => route('notifications.announcements.show', $this->announcement),
                'actionText' => 'فتح الإشعار داخل النظام',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->announcement->title,
            'message' => Str::limit(trim((string) $this->announcement->message), 180),
            'url' => route('notifications.announcements.show', $this->announcement),
            'type' => 'announcement_broadcast',
            'announcement_id' => (int) $this->announcement->id,
            'sender_name' => (string) ($this->announcement->sender?->name ?? 'الإدارة'),
            'sender_role' => (string) ($this->announcement->sender?->role ?? ''),
            'link_url' => $this->announcement->link_url,
            'has_image' => (bool) $this->announcement->image_path,
        ];
    }
}
