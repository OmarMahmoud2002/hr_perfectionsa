<?php

namespace App\Services\DailyPerformance;

use App\Models\DailyPerformanceEntry;
use App\Models\DailyPerformanceReview;
use App\Models\Employee;
use App\Models\User;
use App\Services\Department\DepartmentScopeService;
use App\Services\Notifications\EmailNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DailyPerformanceReviewService
{
    private const ALLOWED_REVIEWER_ROLES = ['admin', 'manager', 'hr', 'user', 'department_manager'];

    public function __construct(
        private readonly DepartmentScopeService $departmentScopeService,
        private readonly EmailNotificationService $emailNotificationService,
    ) {}

    /**
     * @return array{cards: Collection<int, Employee>, filters: array<string, mixed>, stats: array<string, int|float>}
     */
    public function getReviewDashboard(User $reviewer, array $filters = []): array
    {
        $this->assertReviewerRole($reviewer);

        $selectedDate = $this->normalizeDate($filters['date'] ?? null);
        $employeeId = isset($filters['employee_id']) && (int) $filters['employee_id'] > 0
            ? (int) $filters['employee_id']
            : null;
        $status = $this->normalizeStatus((string) ($filters['status'] ?? 'all'));

        $employeesQuery = Employee::query()
            ->active()
            ->whereHas('user', fn ($q) => $q->where('role', 'employee'))
            ->withExists([
                'dailyPerformanceEntries as has_daily_entry' => fn ($q) => $q->whereDate('work_date', $selectedDate),
            ])
            ->with([
                'user:id,employee_id,name',
                'user.profile:id,user_id,avatar_path',
                'dailyPerformanceEntries' => fn ($q) => $q
                    ->whereDate('work_date', $selectedDate)
                    ->with([
                        'attachments',
                        'links',
                        'reviews.reviewer:id,name',
                    ]),
            ])
            ->orderByDesc('has_daily_entry')
            ->orderBy('name');

        if (! $reviewer->isEvaluatorUser()) {
            $this->departmentScopeService->applyEmployeeScope($employeesQuery, $reviewer);
        }

        if ($employeeId !== null) {
            $employeesQuery->whereKey($employeeId);
        }

        if ($status === 'submitted') {
            $employeesQuery->whereHas('dailyPerformanceEntries', fn ($q) => $q->whereDate('work_date', $selectedDate));
        }

        if ($status === 'not_submitted') {
            $employeesQuery->whereDoesntHave('dailyPerformanceEntries', fn ($q) => $q->whereDate('work_date', $selectedDate));
        }

        $cards = $employeesQuery->get();

        $stats = $this->getReviewStats($selectedDate, $employeeId, $reviewer);

        return [
            'cards' => $cards,
            'filters' => [
                'date' => $selectedDate,
                'employee_id' => $employeeId,
                'status' => $status,
            ],
            'stats' => $stats,
        ];
    }

    public function upsertReview(User $reviewer, DailyPerformanceEntry $entry, int $rating, ?string $comment = null): DailyPerformanceReview
    {
        $this->assertReviewerRole($reviewer);

        if ($rating < 1 || $rating > 5) {
            throw new RuntimeException('قيمة التقييم يجب أن تكون بين 1 و 5.');
        }

        $review = DB::transaction(function () use ($reviewer, $entry, $rating, $comment) {
            $existing = DailyPerformanceReview::query()
                ->where('entry_id', $entry->id)
                ->where('reviewer_user_id', $reviewer->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->update([
                    'rating' => $rating,
                    'comment' => $comment,
                    'reviewed_at' => now(),
                ]);

                return $existing->fresh(['reviewer:id,name']);
            }

            return DailyPerformanceReview::query()->create([
                'entry_id' => $entry->id,
                'reviewer_user_id' => $reviewer->id,
                'rating' => $rating,
                'comment' => $comment,
                'reviewed_at' => now(),
            ])->fresh(['reviewer:id,name']);
        });

        $entry->loadMissing('employee.user');
        $this->emailNotificationService->notifyDailyPerformanceReviewed($entry, $reviewer, $review->rating, $review->comment);

        return $review;
    }

    public function getEmployeeEntryRatingSummary(DailyPerformanceEntry $entry): array
    {
        $entry->loadMissing('reviews.reviewer:id,name');

        $reviews = $entry->reviews;
        $count = $reviews->count();

        return [
            'reviews_count' => $count,
            'average_rating' => $count > 0 ? round((float) $reviews->avg('rating'), 2) : null,
            'reviews' => $reviews,
        ];
    }

    /**
     * @return array{total_employees: int, submitted_count: int, not_submitted_count: int, submission_rate: float}
     */
    private function getReviewStats(string $date, ?int $employeeId = null, ?User $reviewer = null): array
    {
        $baseEmployees = Employee::query()
            ->active()
            ->whereHas('user', fn ($q) => $q->where('role', 'employee'));

        if ($reviewer !== null && ! $reviewer->isEvaluatorUser()) {
            $this->departmentScopeService->applyEmployeeScope($baseEmployees, $reviewer);
        }

        if ($employeeId !== null) {
            $baseEmployees->whereKey($employeeId);
        }

        $totalEmployees = (clone $baseEmployees)->count();

        $submittedCount = (clone $baseEmployees)
            ->whereHas('dailyPerformanceEntries', fn ($q) => $q->whereDate('work_date', $date))
            ->count();

        $notSubmittedCount = max(0, $totalEmployees - $submittedCount);

        return [
            'total_employees' => $totalEmployees,
            'submitted_count' => $submittedCount,
            'not_submitted_count' => $notSubmittedCount,
            'submission_rate' => $totalEmployees > 0
                ? round(($submittedCount / $totalEmployees) * 100, 2)
                : 0.0,
        ];
    }

    private function normalizeDate(mixed $date): string
    {
        try {
            return $date ? Carbon::parse((string) $date)->toDateString() : now()->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, ['all', 'submitted', 'not_submitted'], true)
            ? $status
            : 'all';
    }

    private function assertReviewerRole(User $reviewer): void
    {
        if (! in_array($reviewer->role, self::ALLOWED_REVIEWER_ROLES, true)) {
            throw new RuntimeException('هذه العملية متاحة للمقيمين فقط.');
        }
    }
}
