<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DiagnoseNotifications extends Command
{
    protected $signature = 'notifications:diagnose {--to= : Send a direct SMTP test email to this address}';

    protected $description = 'Diagnose notification queue and mail delivery configuration.';

    public function handle(): int
    {
        $queue = (string) Config::get('queue.default');
        $mailer = (string) Config::get('mail.default');
        $mailConfig = (array) Config::get("mail.mailers.{$mailer}", []);
        $from = (array) Config::get('mail.from', []);

        $this->info('Notification diagnostics');
        $this->table(['Setting', 'Value'], [
            ['Queue connection', $queue],
            ['Mail mailer', $mailer],
            ['Mail transport', (string) ($mailConfig['transport'] ?? '-')],
            ['Mail host', (string) ($mailConfig['host'] ?? '-')],
            ['Mail port', (string) ($mailConfig['port'] ?? '-')],
            ['Mail encryption', (string) ($mailConfig['encryption'] ?? 'none')],
            ['Mail timeout', (string) ($mailConfig['timeout'] ?? 'default')],
            ['From address', (string) ($from['address'] ?? '-')],
        ]);

        $this->line('Queue tables');
        $this->table(['Table', 'Status', 'Count'], [
            ['jobs', $this->tableStatus('jobs'), $this->tableCount('jobs')],
            ['failed_jobs', $this->tableStatus('failed_jobs'), $this->tableCount('failed_jobs')],
            ['notifications', $this->tableStatus('notifications'), $this->tableCount('notifications')],
        ]);

        if (($mailConfig['transport'] ?? null) === 'smtp') {
            $this->testSmtpSocket($mailConfig);
        }

        $to = trim((string) $this->option('to'));
        if ($to !== '') {
            return $this->sendTestMail($to);
        }

        $this->comment('Pass --to=email@example.com to send a direct SMTP test email.');

        return self::SUCCESS;
    }

    private function tableStatus(string $table): string
    {
        try {
            return Schema::hasTable($table) ? 'exists' : 'missing';
        } catch (Throwable $exception) {
            return 'error: '.$exception->getMessage();
        }
    }

    private function tableCount(string $table): string
    {
        try {
            if (! Schema::hasTable($table)) {
                return '-';
            }

            return (string) DB::table($table)->count();
        } catch (Throwable $exception) {
            return 'error';
        }
    }

    private function testSmtpSocket(array $mailConfig): void
    {
        $host = (string) ($mailConfig['host'] ?? '');
        $port = (int) ($mailConfig['port'] ?? 0);
        $timeout = (float) ($mailConfig['timeout'] ?? 10);

        if ($host === '' || $port <= 0) {
            $this->warn('SMTP socket test skipped: host or port is missing.');
            return;
        }

        $errno = 0;
        $errstr = '';
        $startedAt = microtime(true);
        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT
        );
        $elapsed = number_format((microtime(true) - $startedAt) * 1000, 0);

        if (is_resource($socket)) {
            fclose($socket);
            $this->info("SMTP socket OK: {$host}:{$port} connected in {$elapsed}ms.");
            return;
        }

        $this->error("SMTP socket FAILED: {$host}:{$port} after {$elapsed}ms. {$errno} {$errstr}");
    }

    private function sendTestMail(string $to): int
    {
        try {
            Mail::raw('This is a direct SMTP test from Perfection System.', function ($message) use ($to): void {
                $message->to($to)->subject('Perfection System SMTP test');
            });

            $this->info("Direct SMTP test email sent to {$to}.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Direct SMTP test failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
