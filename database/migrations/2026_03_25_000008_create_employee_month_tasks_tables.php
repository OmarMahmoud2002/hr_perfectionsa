<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_month_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['period_year', 'period_month'], 'employee_month_tasks_year_month_index');
            $table->index('is_active', 'employee_month_tasks_is_active_index');
        });

        Schema::create('employee_month_task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                ->constrained('employee_month_tasks')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'employee_id'], 'employee_month_task_assignments_task_employee_unique');
            $table->index('employee_id', 'employee_month_task_assignments_employee_index');
        });

        Schema::create('employee_month_task_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                ->constrained('employee_month_tasks')
                ->cascadeOnDelete();
            $table->foreignId('evaluator_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique('task_id', 'employee_month_task_evaluations_task_unique');
            $table->index('score', 'employee_month_task_evaluations_score_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_month_task_evaluations');
        Schema::dropIfExists('employee_month_task_assignments');
        Schema::dropIfExists('employee_month_tasks');
    }
};
