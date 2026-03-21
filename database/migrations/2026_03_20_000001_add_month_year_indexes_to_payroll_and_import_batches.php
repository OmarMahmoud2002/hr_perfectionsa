<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_reports', function (Blueprint $table) {
            $table->index(['year', 'month'], 'payroll_reports_year_month_index');
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->index(['year', 'month', 'status'], 'import_batches_year_month_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_reports', function (Blueprint $table) {
            $table->dropIndex('payroll_reports_year_month_index');
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropIndex('import_batches_year_month_status_index');
        });
    }
};
