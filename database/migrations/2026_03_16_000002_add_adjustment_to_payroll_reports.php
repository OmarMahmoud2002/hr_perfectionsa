<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_reports', function (Blueprint $table) {
            $table->decimal('extra_bonus', 10, 2)->default(0)->after('attendance_bonus');
            $table->decimal('extra_deduction', 10, 2)->default(0)->after('extra_bonus');
            $table->string('adjustment_note', 255)->nullable()->after('extra_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_reports', function (Blueprint $table) {
            $table->dropColumn(['extra_bonus', 'extra_deduction', 'adjustment_note']);
        });
    }
};
