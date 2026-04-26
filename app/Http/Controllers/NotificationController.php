<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'notificationSummary' => [
                'total' => $user->notifications()->count(),
                'unread' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->first();

        if ($notification instanceof DatabaseNotification && $notification->read_at === null) {
            $notification->markAsRead();
        }

        return back();
    }

    public function open(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->first();

        if (! $notification instanceof DatabaseNotification) {
            return redirect()->route('notifications.index');
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $url = (string) data_get($notification->data, 'url', '');

        if ($url === '' || $url === '#') {
            return redirect()->route('notifications.index');
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return redirect()->away($url);
        }

        return redirect($url);
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications
            ->markAsRead();

        return back();
    }
}
