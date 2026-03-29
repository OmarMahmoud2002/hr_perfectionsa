<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_performance_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->date('work_date');
            $table->string('project_name');
            $table->text('work_description');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date'], 'daily_performance_entries_employee_work_date_unique');
            $table->index('work_date', 'daily_performance_entries_work_date_index');
            $table->index('employee_id', 'daily_performance_entries_employee_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_performance_entries');
    }
};
