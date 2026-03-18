<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_reports', function (Blueprint $table) {
            $table->decimal('net_salary_final', 10, 2)->default(0)->after('adjustment_note')->comment('الصافي النهائي بعد التسوية');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_reports', function (Blueprint $table) {
            $table->dropColumn('net_salary_final');
        });
    }
};
