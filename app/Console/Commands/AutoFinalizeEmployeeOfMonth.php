<?php

namespace App\Console\Commands;

use App\Services\EmployeeOfMonth\EmployeeOfMonthFinalizationService;
use App\Services\Payroll\PayrollPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoFinalizeEmployeeOfMonth extends Command
{
    protected $signature = 'employee-of-month:auto-finalize {--month=} {--year=} {--tenant=eg}';

    protected $description = 'Auto finalize Employee of Month results for payroll period.';

    public function __construct(
        private readonly EmployeeOfMonthFinalizationService $finalizationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenant = (string) $this->option('tenant');
        $connection = match ($tenant) {
            'sa' => 'mysql_sa',
            default => 'mysql_eg',
        };

        config([
            'app.tenant' => $tenant === 'sa' ? 'sa' : 'eg',
            'database.default' => $connection,
        ]);

        DB::setDefaultConnection($connection);
        DB::purge($connection);
        DB::reconnect($connection);

        $month = $this->option('month');
        $year = $this->option('year');

        if ($month === null || $year === null) {
            $period = PayrollPeriod::monthForDate(now());
            $month = (int) $period['month'];
            $year = (int) $period['year'];
        } else {
            $month = (int) $month;
            $year = (int) $year;
        }

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            $this->error('Invalid month/year values.');
            return self::FAILURE;
        }

        $result = $this->finalizationService->finalizeMonth($month, $year);

        $this->info("Employee of Month finalized for {$month}/{$year} ({$tenant}).");
        $this->line('Rows saved: ' . $result['rows_count']);

        return self::SUCCESS;
    }
}
