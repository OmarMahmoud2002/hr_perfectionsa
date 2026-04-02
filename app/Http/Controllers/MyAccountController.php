<?php

namespace App\Http\Controllers;

use App\Enums\ImportStatus;
use App\Models\EmployeeOfMonthResult;
use App\Models\ImportBatch;
use App\Services\Attendance\AbsenceDetectionService;
use App\Services\Attendance\PublicHolidayService;
use App\Services\Payroll\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MyAccountController extends Controller
{
    public function __construct(
        private readonly AbsenceDetectionService $absenceService,
        private readonly PublicHolidayService $holidayService,
    ) {}

    public function show(Request $request): View
    {
        $user = $request->user()->loadMissing('profile', 'employee');

        $monthData = PayrollPeriod::monthForDate(now());
        $defaultMonth = (int) $monthData['month'];
        $defaultYear = (int) $monthData['year'];

        $cookieMonth = (int) $request->cookie('employee_stats_month', 0);
        $cookieYear = (int) $request->cookie('employee_stats_year', 0);

        $month = $request->has('month')
            ? (int) $request->input('month')
            : ($cookieMonth > 0 ? $cookieMonth : $defaultMonth);

        $year = $request->has('year')
            ? (int) $request->input('year')
            : ($cookieYear > 0 ? $cookieYear : $defaultYear);

        if ($month < 1 || $month > 12) {
            $month = $defaultMonth;
        }

        if ($year < 2000 || $year > 2100) {
            $year = $defaultYear;
        }

        Cookie::queue(cookie('employee_stats_month', (string) $month, 60 * 24 * 365));
        Cookie::queue(cookie('employee_stats_year', (string) $year, 60 * 24 * 365));

        [$periodStartDate, $periodEndDate] = PayrollPeriod::resolve($month, $year);

        $stats = null;
        $dailyBreakdown = collect();
        $employeeOfMonthWinsCount = 0;
        $employeeOfMonthWinMonths = collect();

        if ($user->employee) {
            $batch = ImportBatch::where('month', $month)
                ->where('year', $year)
                ->where('status', ImportStatus::Completed)
                ->latest('id')
                ->first();

            $publicHolidays = $batch ? $this->holidayService->getHolidayDates($batch) : [];

            $monthlyStats = $this->absenceService->getMonthlyStats($user->employee, $month, $year, $publicHolidays);
            $stats = [
                'present' => $monthlyStats['total_present_days'],
                'absent' => $monthlyStats['total_absent_days'],
                'late_minutes' => $monthlyStats['total_late_minutes'],
                'overtime_minutes' => $monthlyStats['total_overtime_minutes'],
                'weekly_leave' => $monthlyStats['total_weekly_leave_days'],
                'working_days' => $monthlyStats['total_working_days'],
            ];

            $dailyBreakdown = $this->absenceService->getDailyBreakdown($user->employee, $month, $year, $publicHolidays);

            $monthlyWinners = EmployeeOfMonthResult::query()
                ->select(['employee_id', 'month', 'year', 'final_score', 'breakdown', 'generated_at'])
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get()
                ->groupBy(fn (EmployeeOfMonthResult $row) => sprintf('%04d-%02d', (int) $row->year, (int) $row->month))
                ->map(function ($group) {
                    return $group
                        ->sort(function (EmployeeOfMonthResult $a, EmployeeOfMonthResult $b) {
                            $finalCompare = ((float) $b->final_score) <=> ((float) $a->final_score);
                            if ($finalCompare !== 0) {
                                return $finalCompare;
                            }

                            $taskA = (float) data_get($a->breakdown, 'task_points', data_get($a->breakdown, 'task_score', 0));
                            $taskB = (float) data_get($b->breakdown, 'task_points', data_get($b->breakdown, 'task_score', 0));
                            $taskCompare = $taskB <=> $taskA;
                            if ($taskCompare !== 0) {
                                return $taskCompare;
                            }

                            $aTs = $a->generated_at?->getTimestamp() ?? 0;
                            $bTs = $b->generated_at?->getTimestamp() ?? 0;

                            return $bTs <=> $aTs;
                        })
                        ->first();
                })
                ->filter()
                ->values();

            $employeeWins = $monthlyWinners
                ->where('employee_id', (int) $user->employee->id)
                ->values();

            $employeeOfMonthWinsCount = $employeeWins->count();
            $employeeOfMonthWinMonths = $employeeWins
                ->map(fn (EmployeeOfMonthResult $row) => Carbon::create((int) $row->year, (int) $row->month, 1)->locale('ar')->isoFormat('MMMM YYYY'))
                ->values();
        }

        return view('account.my', [
            'user' => $user,
            'month' => $month,
            'year' => $year,
            'periodStart' => $periodStartDate->toDateString(),
            'periodEnd' => $periodEndDate->toDateString(),
            'stats' => $stats,
            'dailyBreakdown' => $dailyBreakdown,
            'employeeOfMonthWinsCount' => $employeeOfMonthWinsCount,
            'employeeOfMonthWinMonths' => $employeeOfMonthWinMonths,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user()->loadMissing('profile');

        $validated = $request->validate([
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'bio' => ['nullable', 'string', 'max:1500'],
            'social_link_1' => ['nullable', 'url', 'max:500'],
            'social_link_2' => ['nullable', 'url', 'max:500'],
        ]);

        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        if ($request->hasFile('avatar')) {
            if ($profile->avatar_path) {
                Storage::disk('public')->delete($profile->avatar_path);
            }

            $validated['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        unset($validated['avatar']);

        $profile->update($validated);

        return back()->with('success', 'تم تحديث بيانات الملف الشخصي بنجاح.');
    }

    public function avatar(string $path): BinaryFileResponse
    {
        if (str_contains($path, '..')) {
            abort(404);
        }

        $cleanPath = ltrim($path, '/');

        if (! Storage::disk('public')->exists($cleanPath)) {
            abort(404);
        }

        $fullPath = storage_path('app/public/' . $cleanPath);

        return response()->file($fullPath, [
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    public function taskAttachment(string $path): BinaryFileResponse
    {
        if (str_contains($path, '..')) {
            abort(404);
        }

        $cleanPath = ltrim($path, '/');

        // Restrict this endpoint to task attachments only.
        if (! str_starts_with($cleanPath, 'task-attachments/')) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($cleanPath)) {
            abort(404);
        }

        $fullPath = storage_path('app/public/' . $cleanPath);

        return response()->file($fullPath, [
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
