<?php

namespace App\Console\Commands;

use App\Services\EmployeeOfMonth\EmployeeOfMonthFinalizationService;
use App\Services\Payroll\PayrollPeriod;
use Illuminate\Console\Command;

class AutoFinalizeEmployeeOfMonth extends Command
{
    protected $signature = 'employee-of-month:auto-finalize {--month=} {--year=}';

    protected $description = 'Auto finalize Employee of Month results for payroll period.';

    public function __construct(
        private readonly EmployeeOfMonthFinalizationService $finalizationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
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

        $this->info("Employee of Month finalized for {$month}/{$year}.");
        $this->line('Rows saved: ' . $result['rows_count']);

        return self::SUCCESS;
    }
}
