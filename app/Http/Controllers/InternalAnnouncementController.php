<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\Notifications\AnnouncementDispatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InternalAnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementDispatchService $announcementDispatchService,
    ) {}

    public function create(Request $request): View
    {
        $employees = Employee::query()
            ->where('is_active', true)
            ->whereHas('user')
            ->with(['department:id,name', 'jobTitleRef:id,name_ar'])
            ->orderBy('name')
            ->get(['id', 'name', 'department_id', 'job_title_id']);

        $departments = Department::query()
            ->where('is_active', true)
            ->withCount([
                'employees as employees_with_accounts_count' => fn ($query) => $query
                    ->where('is_active', true)
                    ->whereHas('user'),
            ])
            ->orderBy('name')
            ->get(['id', 'name']);

        $jobTitles = JobTitle::query()
            ->where('is_active', true)
            ->withCount([
                'employees as employees_with_accounts_count' => fn ($query) => $query
                    ->where('is_active', true)
                    ->whereHas('user'),
            ])
            ->orderBy('name_ar')
            ->get(['id', 'name_ar']);

        return view('notifications.compose', [
            'employees' => $employees,
            'departments' => $departments,
            'jobTitles' => $jobTitles,
            'composeSummary' => [
                'employees' => $employees->count(),
                'departments' => $departments->count(),
                'job_titles' => $jobTitles->count(),
            ],
        ]);
    }

    public function index(Request $request): View
    {
        $this->announcementDispatchService->ensureAnnouncementsTableExists();

        $search = trim((string) $request->query('search', ''));

        $announcements = Announcement::query()
            ->with('sender')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($announcementQuery) use ($search): void {
                    $announcementQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('sender', fn ($senderQuery) => $senderQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('notifications.sent-index', [
            'announcements' => $announcements,
            'search' => $search,
            'sentSummary' => [
                'total' => Announcement::query()->count(),
                'with_images' => Announcement::query()->whereNotNull('image_path')->count(),
            ],
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $result = $this->announcementDispatchService->dispatch(
            $request->user(),
            $request->validated(),
            $request->file('image'),
        );

        $announcement = $result['announcement'];
        $recipients = $result['recipients'];

        if (! $announcement instanceof Announcement || $recipients->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', 'لم يتم العثور على حسابات موظفين نشطة يمكن إرسال الإشعار إليها.');
        }

        $emailRecipients = $result['email_recipients'];

        return redirect()
            ->route('notifications.compose')
            ->with('success', 'تم إرسال الإشعار بنجاح إلى '.$recipients->count().' موظف.')
            ->with('info', 'تم أيضًا إرساله بريدياً إلى '.$emailRecipients->count().' حساب لديه بريد إلكتروني.');
    }

    public function show(Request $request, Announcement $announcement): View
    {
        $announcement->loadMissing('sender');
        $user = $request->user();

        abort_unless($this->canViewAnnouncement($user, $announcement), 403);

        $matchedNotifications = $this->matchedAnnouncementNotifications($user, $announcement);
        $matchedNotifications
            ->filter(fn ($notification) => $notification->read_at === null)
            ->each
            ->markAsRead();

        return view('notifications.show-announcement', [
            'announcement' => $announcement,
            'announcementImageUrl' => $announcement->image_path
                ? route('media.announcement.file', ['path' => $announcement->image_path])
                : null,
            'senderRoleLabel' => $this->roleLabel((string) ($announcement->sender?->role ?? '')),
            'audienceLabels' => (array) data_get($announcement->audience_meta, 'labels', []),
        ]);
    }

    public function image(string $path): BinaryFileResponse
    {
        if (str_contains($path, '..')) {
            abort(404);
        }

        $cleanPath = ltrim($path, '/');

        if (! str_starts_with($cleanPath, 'announcements/')) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($cleanPath)) {
            abort(404);
        }

        return response()->file(storage_path('app/public/' . $cleanPath), [
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    private function canViewAnnouncement(User $user, Announcement $announcement): bool
    {
        if ((int) $announcement->sender_user_id === (int) $user->id) {
            return true;
        }

        return $this->matchedAnnouncementNotifications($user, $announcement)->isNotEmpty();
    }

    private function matchedAnnouncementNotifications(User $user, Announcement $announcement): Collection
    {
        return $user->notifications()
            ->latest()
            ->get()
            ->filter(fn ($notification) => (int) data_get($notification->data, 'announcement_id', 0) === (int) $announcement->id)
            ->values();
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'مدير النظام',
            'manager' => 'المدير العام',
            'hr' => 'الموارد البشرية',
            'department_manager' => 'مدير قسم',
            'employee', 'office_girl' => 'موظف',
            default => 'الإدارة',
        };
    }
}
