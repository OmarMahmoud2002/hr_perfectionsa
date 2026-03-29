<?php

namespace App\Services\DailyPerformance;

use App\Models\DailyPerformanceAttachment;
use App\Models\DailyPerformanceEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DailyPerformanceEntryService
{
    private const MAX_ATTACHMENTS_PER_ENTRY = 5;

    /**
     * @param Collection<int, UploadedFile>|array<int, UploadedFile> $files
     */
    public function upsertForEmployee(User $user, array $payload, Collection|array $files = []): DailyPerformanceEntry
    {
        $employee = $this->assertEmployeeContext($user);
        $workDate = Carbon::parse((string) ($payload['work_date'] ?? now()->toDateString()))->toDateString();

        return DB::transaction(function () use ($employee, $payload, $files, $workDate) {
            $entry = DailyPerformanceEntry::query()->firstOrNew([
                'employee_id' => $employee->id,
                'work_date' => $workDate,
            ]);

            $entry->fill([
                'project_name' => (string) $payload['project_name'],
                'work_description' => (string) $payload['work_description'],
                'submitted_at' => now(),
            ]);
            $entry->save();

            $normalizedFiles = collect($files)->filter(fn ($file) => $file instanceof UploadedFile)->values();
            if ($normalizedFiles->isNotEmpty()) {
                $currentCount = $entry->attachments()->count();
                if ($currentCount + $normalizedFiles->count() > self::MAX_ATTACHMENTS_PER_ENTRY) {
                    throw new RuntimeException('الحد الأقصى للمرفقات لكل سجل هو 5 ملفات.');
                }

                foreach ($normalizedFiles as $file) {
                    $storedPath = $file->store("daily-performance/{$employee->id}/{$workDate}", 'public');

                    $entry->attachments()->create([
                        'disk' => 'public',
                        'path' => $storedPath,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'is_image' => str_starts_with((string) $file->getClientMimeType(), 'image/'),
                    ]);
                }
            }

            return $entry->fresh([
                'attachments',
                'reviews.reviewer:id,name',
            ]);
        });
    }

    public function getEmployeeEntryByDate(User $user, string $workDate): ?DailyPerformanceEntry
    {
        $employee = $this->assertEmployeeContext($user);

        return DailyPerformanceEntry::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate)
            ->with([
                'attachments',
                'reviews.reviewer:id,name',
            ])
            ->first();
    }

    /**
     * @return array{date: string, has_entry: bool, entry_id: int|null}
     */
    public function getEmployeeTimelineItem(User $user, string $workDate): array
    {
        $entry = $this->getEmployeeEntryByDate($user, $workDate);

        return [
            'date' => $workDate,
            'has_entry' => $entry !== null,
            'entry_id' => $entry?->id,
        ];
    }

    /**
     * @return Collection<int, array{date: string, has_entry: bool, entry_id: int|null}>
     */
    public function getLastDaysTimeline(User $user, int $days = 7): Collection
    {
        $employee = $this->assertEmployeeContext($user);
        $days = max(1, min($days, 31));

        $from = now()->copy()->subDays($days - 1)->toDateString();
        $to = now()->toDateString();

        $entryIdsByDate = DailyPerformanceEntry::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$from, $to])
            ->pluck('id', 'work_date');

        return collect(range(0, $days - 1))
            ->map(function (int $offset) use ($entryIdsByDate) {
                $date = now()->copy()->subDays($offset)->toDateString();
                $entryId = $entryIdsByDate[$date] ?? null;

                return [
                    'date' => $date,
                    'has_entry' => $entryId !== null,
                    'entry_id' => $entryId,
                ];
            })
            ->values();
    }

    public function deleteAttachment(User $user, DailyPerformanceAttachment $attachment): void
    {
        $employee = $this->assertEmployeeContext($user);
        $attachment->loadMissing('entry');

        if ((int) $attachment->entry->employee_id !== (int) $employee->id) {
            throw new RuntimeException('لا يمكنك حذف مرفقات تخص موظفا آخر.');
        }

        DB::transaction(function () use ($attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
        });
    }

    private function assertEmployeeContext(User $user)
    {
        $user->loadMissing('employee');

        if (! $user->isEmployee()) {
            throw new RuntimeException('هذه العملية متاحة للموظفين فقط.');
        }

        if (! $user->employee) {
            throw new RuntimeException('الحساب الحالي غير مرتبط بموظف.');
        }

        return $user->employee;
    }
}
