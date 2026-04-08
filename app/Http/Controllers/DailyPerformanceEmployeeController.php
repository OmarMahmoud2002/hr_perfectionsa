<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyPerformanceEntryRequest;
use App\Models\DailyPerformanceAttachment;
use App\Services\DailyPerformance\DailyPerformanceEntryService;
use App\Services\DailyPerformance\DailyPerformanceReviewService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DailyPerformanceEmployeeController extends Controller
{
    public function __construct(
        private readonly DailyPerformanceEntryService $entryService,
        private readonly DailyPerformanceReviewService $reviewService,
    ) {}

    public function index(Request $request): View
    {
        $today        = now()->toDateString();
        $selectedDate = $this->normalizeDate((string) $request->input('date', $today));
        // Future dates are clamped to today so employees cannot navigate ahead.
        if ($selectedDate > $today) {
            $selectedDate = $today;
        }
        $isToday = ($selectedDate === $today);

        $entry = $this->entryService->getEmployeeEntryByDate($request->user(), $selectedDate);
        $timeline = $this->entryService->getLastDaysTimeline($request->user(), 7);

        $ratingSummary = $entry
            ? $this->reviewService->getEmployeeEntryRatingSummary($entry)
            : [
                'reviews_count' => 0,
                'average_rating' => null,
                'reviews' => collect(),
            ];

        return view('daily-performance.employee', [
            'selectedDate' => $selectedDate,
            'isToday'      => $isToday,
            'prevDate'     => Carbon::parse($selectedDate)->subDay()->toDateString(),
            'nextDate'     => Carbon::parse($selectedDate)->addDay()->toDateString(),
            'entry'        => $entry,
            'timeline'     => $timeline,
            'ratingSummary' => $ratingSummary,
        ]);
    }

    public function upsert(StoreDailyPerformanceEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->entryService->upsertForEmployee(
                $request->user(),
                $validated,
                $request->file('attachments', []),
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم حفظ الأداء اليومي بنجاح.');
    }

    public function destroyAttachment(Request $request, DailyPerformanceAttachment $attachment): RedirectResponse
    {
        try {
            $this->entryService->deleteAttachment($request->user(), $attachment);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم حذف المرفق بنجاح.');
    }

    public function media(Request $request, string $path): BinaryFileResponse
    {
        if (str_contains($path, '..')) {
            abort(404);
        }

        $cleanPath = ltrim($path, '/');

        if (! str_starts_with($cleanPath, 'daily-performance/')) {
            abort(404);
        }

        $attachment = DailyPerformanceAttachment::query()
            ->where('path', $cleanPath)
            ->with('entry')
            ->first();

        if (! $attachment) {
            abort(404);
        }

        $role = (string) $request->user()->role;
        $isReviewer = in_array($role, ['admin', 'manager', 'hr', 'user'], true);
        $isOwnerEmployee = in_array($role, ['employee', 'office_girl'], true)
            && (int) $request->user()->employee_id === (int) $attachment->entry->employee_id;

        if (! $isReviewer && ! $isOwnerEmployee) {
            abort(403, 'ليس لديك صلاحية للوصول إلى هذا الملف.');
        }

        if (! Storage::disk($attachment->disk)->exists($cleanPath)) {
            abort(404);
        }

        return response()->file(storage_path('app/public/' . $cleanPath), [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function normalizeDate(string $date): string
    {
        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }
}
