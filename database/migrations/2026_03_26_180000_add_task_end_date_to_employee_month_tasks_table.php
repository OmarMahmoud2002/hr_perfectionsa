<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employee_month_tasks', 'task_end_date')) {
            Schema::table('employee_month_tasks', function (Blueprint $table) {
                $table->date('task_end_date')->nullable()->after('task_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employee_month_tasks', 'task_end_date')) {
            Schema::table('employee_month_tasks', function (Blueprint $table) {
                $table->dropColumn('task_end_date');
            });
        }
    }
};
