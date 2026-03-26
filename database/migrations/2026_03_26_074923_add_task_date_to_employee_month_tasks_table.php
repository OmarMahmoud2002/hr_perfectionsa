<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('employee_month_tasks', 'task_date')) {
            Schema::table('employee_month_tasks', function (Blueprint $table) {
                $table->date('task_date')->nullable()->after('period_end_date');
                $table->index('task_date', 'employee_month_tasks_task_date_index');
            });
        }

        DB::table('employee_month_tasks')
            ->whereNull('task_date')
            ->update(['task_date' => now()->toDateString()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('employee_month_tasks', 'task_date')) {
            Schema::table('employee_month_tasks', function (Blueprint $table) {
                $table->dropIndex('employee_month_tasks_task_date_index');
                $table->dropColumn('task_date');
            });
        }
    }
};
