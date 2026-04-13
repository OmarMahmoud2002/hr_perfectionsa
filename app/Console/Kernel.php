<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('employee-of-month:auto-finalize --tenant=eg')
            ->monthlyOn(21, '23:45')
            ->withoutOverlapping();

        $schedule->command('employee-of-month:auto-finalize --tenant=sa')
            ->monthlyOn(21, '23:50')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
