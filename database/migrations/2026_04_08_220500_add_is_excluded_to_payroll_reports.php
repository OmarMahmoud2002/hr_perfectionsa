<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_reports', function (Blueprint $table) {
            $table->boolean('is_excluded')->default(false)->after('is_locked');
            $table->index(['year', 'month', 'is_excluded'], 'payroll_reports_year_month_excluded_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_reports', function (Blueprint $table) {
            $table->dropIndex('payroll_reports_year_month_excluded_idx');
            $table->dropColumn('is_excluded');
        });
    }
};
