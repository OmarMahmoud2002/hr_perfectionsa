<?php

namespace App\Services\Notifications;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class NotificationInfrastructureService
{
    /**
     * @var array<string, bool>
     */
    private array $preparedConnections = [];

    public function ensureCoreTablesExist(): bool
    {
        $connection = $this->currentConnectionName();

        if (($this->preparedConnections[$connection] ?? false) === true) {
            return true;
        }

        try {
            $this->ensureNotificationsTableExists();
            $this->ensureAnnouncementsTableExists();
            $this->ensureJobsTableExistsWhenNeeded();

            $this->preparedConnections[$connection] = true;

            return true;
        } catch (Throwable $exception) {
            Log::warning('Notification infrastructure could not be prepared.', [
                'connection' => $connection,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function ensureNotificationTablesExist(): bool
    {
        $connection = $this->currentConnectionName();

        try {
            $this->ensureNotificationsTableExists();
            $this->ensureJobsTableExistsWhenNeeded();

            return true;
        } catch (Throwable $exception) {
            Log::warning('Notification tables could not be prepared.', [
                'connection' => $connection,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function ensureAnnouncementsTableExists(): bool
    {
        try {
            $schema = $this->schema();

            if ($schema->hasTable('announcements')) {
                return true;
            }

            $schema->create('announcements', function (Blueprint $table): void {
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

            return true;
        } catch (Throwable $exception) {
            Log::warning('Announcements table could not be prepared.', [
                'connection' => $this->currentConnectionName(),
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function notificationsTableExists(): bool
    {
        try {
            return $this->schema()->hasTable('notifications');
        } catch (Throwable $exception) {
            Log::warning('Notifications table existence check failed.', [
                'connection' => $this->currentConnectionName(),
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function ensureNotificationsTableExists(): void
    {
        $schema = $this->schema();

        if ($schema->hasTable('notifications')) {
            return;
        }

        $schema->create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    private function ensureJobsTableExistsWhenNeeded(): void
    {
        if ((string) Config::get('queue.default') !== 'database') {
            return;
        }

        $tableName = (string) Config::get('queue.connections.database.table', 'jobs');
        $schema = $this->schema();

        if ($schema->hasTable($tableName)) {
            return;
        }

        $schema->create($tableName, function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    private function currentConnectionName(): string
    {
        return (string) DB::getDefaultConnection();
    }

    private function schema(): \Illuminate\Database\Schema\Builder
    {
        return Schema::connection($this->currentConnectionName());
    }
}
