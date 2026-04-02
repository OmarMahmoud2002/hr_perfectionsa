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
        Schema::table('employee_month_task_evaluations', function (Blueprint $table) {
            $table->decimal('score', 4, 1)->change(); // e.g. 7.5, 10.0
        });
    }

    public function down(): void
    {
        Schema::table('employee_month_task_evaluations', function (Blueprint $table) {
            $table->unsignedTinyInteger('score')->change();
        });
    }
};
