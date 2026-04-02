<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employee_month_task_assignments', 'status')) {
            Schema::table('employee_month_task_assignments', function (Blueprint $table) {
                $table->string('status', 20)->default('to_do')->after('employee_id');
                $table->index('status', 'employee_month_task_assignments_status_index');
            });
        }

        DB::table('employee_month_task_assignments')
            ->whereNull('status')
            ->update(['status' => 'to_do']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('employee_month_task_assignments', 'status')) {
            Schema::table('employee_month_task_assignments', function (Blueprint $table) {
                $table->dropIndex('employee_month_task_assignments_status_index');
                $table->dropColumn('status');
            });
        }
    }
};
