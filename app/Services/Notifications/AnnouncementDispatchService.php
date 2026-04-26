<?php

namespace App\Services\Notifications;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\User;
use App\Notifications\AnnouncementBroadcastNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnouncementDispatchService
{
    public function dispatch(User $sender, array $payload, ?UploadedFile $image = null): array
    {
        $this->ensureAnnouncementsTableExists();

        $recipients = $this->resolveRecipients($payload);

        if ($recipients->isEmpty()) {
            return [
                'announcement' => null,
                'recipients' => $recipients,
                'email_recipients' => collect(),
            ];
        }

        $imagePath = $image?->store('announcements', 'public');
        $this->optimizeStoredImage($imagePath);
        $title = $this->resolveTitle($payload['title'] ?? null, (string) $payload['message']);
        $audienceMeta = $this->buildAudienceMeta($payload, $recipients);

        $announcement = Announcement::query()->create([
            'sender_user_id' => (int) $sender->id,
            'title' => $title,
            'message' => (string) $payload['message'],
            'link_url' => $payload['link_url'] ?? null,
            'image_path' => $imagePath,
            'audience_type' => (string) $payload['audience_type'],
            'audience_meta' => $audienceMeta,
            'recipient_count' => $recipients->count(),
        ]);

        $announcement->loadMissing('sender');

        $notification = new AnnouncementBroadcastNotification($announcement);
        $recipients->each(fn (User $user) => $user->notify($notification));

        return [
            'announcement' => $announcement,
            'recipients' => $recipients,
            'email_recipients' => $recipients->filter(fn (User $user) => is_string($user->email) && trim($user->email) !== '')->values(),
        ];
    }

    public function resolveRecipients(array $payload): Collection
    {
        $query = User::query()
            ->whereNotNull('employee_id')
            ->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('is_active', true))
            ->with(['employee.department:id,name', 'employee.jobTitleRef:id,name_ar']);

        $audienceType = (string) ($payload['audience_type'] ?? 'all');

        if ($audienceType === 'employees') {
            $query->whereIn('employee_id', (array) ($payload['employee_ids'] ?? []));
        }

        if ($audienceType === 'departments') {
            $departmentIds = (array) ($payload['department_ids'] ?? []);
            $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->whereIn('department_id', $departmentIds));
        }

        if ($audienceType === 'job_titles') {
            $jobTitleIds = (array) ($payload['job_title_ids'] ?? []);
            $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->whereIn('job_title_id', $jobTitleIds));
        }

        return $query->get()->unique('id')->values();
    }

    private function resolveTitle(?string $title, string $message): string
    {
        if (is_string($title) && trim($title) !== '') {
            return trim($title);
        }

        $message = trim($message);
        if ($message === '') {
            return 'إشعار جديد من الإدارة';
        }

        return Str::limit($message, 72, '');
    }

    private function buildAudienceMeta(array $payload, Collection $recipients): array
    {
        $audienceType = (string) ($payload['audience_type'] ?? 'all');

        return [
            'type' => $audienceType,
            'labels' => match ($audienceType) {
                'employees' => Employee::query()
                    ->whereIn('id', (array) ($payload['employee_ids'] ?? []))
                    ->orderBy('name')
                    ->pluck('name')
                    ->values()
                    ->all(),
                'departments' => Department::query()
                    ->whereIn('id', (array) ($payload['department_ids'] ?? []))
                    ->orderBy('name')
                    ->pluck('name')
                    ->values()
                    ->all(),
                'job_titles' => JobTitle::query()
                    ->whereIn('id', (array) ($payload['job_title_ids'] ?? []))
                    ->orderBy('name_ar')
                    ->pluck('name_ar')
                    ->values()
                    ->all(),
                default => ['كل الموظفين'],
            },
            'recipient_count' => $recipients->count(),
        ];
    }

    public function deleteImageIfExists(?string $imagePath): void
    {
        if (is_string($imagePath) && $imagePath !== '') {
            Storage::disk('public')->delete($imagePath);
        }
    }

    public function ensureAnnouncementsTableExists(): void
    {
        if (Schema::hasTable('announcements')) {
            return;
        }

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('message');
            $table->string('link_url', 2048)->nullable();
            $table->string('image_path')->nullable();
            $table->string('audience_type', 32);
            $table->json('audience_meta')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->timestamps();
        });
    }

    private function optimizeStoredImage(?string $imagePath): void
    {
        if (! is_string($imagePath) || $imagePath === '') {
            return;
        }

        $fullPath = storage_path('app/public/' . ltrim($imagePath, '/'));
        if (! File::exists($fullPath)) {
            return;
        }

        if (extension_loaded('imagick')) {
            $this->optimizeWithImagick($fullPath);
            return;
        }

        if (function_exists('imagecreatefromstring')) {
            $this->optimizeWithGd($fullPath);
        }
    }

    private function optimizeWithImagick(string $fullPath): void
    {
        try {
            $image = new \Imagick($fullPath);
            $image->setImageOrientation(\Imagick::ORIENTATION_UNDEFINED);
            $image->autoOrient();

            if ($image->getImageWidth() > 1600 || $image->getImageHeight() > 1600) {
                $image->thumbnailImage(1600, 1600, true, true);
            }

            $format = strtolower((string) $image->getImageFormat());

            if (in_array($format, ['jpeg', 'jpg'], true)) {
                $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality(88);
                $image->stripImage();
            } elseif ($format === 'png') {
                $image->setImageCompressionQuality(92);
            }

            $image->writeImage($fullPath);
            $image->clear();
            $image->destroy();
        } catch (\Throwable) {
            // Keep original file if optimization fails.
        }
    }

    private function optimizeWithGd(string $fullPath): void
    {
        try {
            $info = @getimagesize($fullPath);
            if (! is_array($info)) {
                return;
            }

            [$width, $height] = $info;
            $mime = (string) ($info['mime'] ?? '');
            if ($width <= 0 || $height <= 0) {
                return;
            }

            $binary = @file_get_contents($fullPath);
            if ($binary === false) {
                return;
            }

            $source = @imagecreatefromstring($binary);
            if (! $source) {
                return;
            }

            $targetWidth = $width;
            $targetHeight = $height;
            $maxDimension = 1600;

            if ($width > $maxDimension || $height > $maxDimension) {
                $ratio = min($maxDimension / $width, $maxDimension / $height);
                $targetWidth = max(1, (int) round($width * $ratio));
                $targetHeight = max(1, (int) round($height * $ratio));
            }

            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

            if (in_array($mime, ['image/png', 'image/webp'], true)) {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
            }

            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            match ($mime) {
                'image/jpeg', 'image/jpg' => imagejpeg($canvas, $fullPath, 88),
                'image/png' => imagepng($canvas, $fullPath, 6),
                'image/webp' => function_exists('imagewebp') ? imagewebp($canvas, $fullPath, 88) : imagepng($canvas, $fullPath, 6),
                default => null,
            };

            imagedestroy($canvas);
            imagedestroy($source);
        } catch (\Throwable) {
            // Keep original file if optimization fails.
        }
    }
}
