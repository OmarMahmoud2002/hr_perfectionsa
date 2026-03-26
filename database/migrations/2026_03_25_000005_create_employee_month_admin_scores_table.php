<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_month_admin_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('score');
            $table->text('note')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year'], 'employee_month_admin_scores_employee_month_year_unique');
            $table->index(['year', 'month'], 'employee_month_admin_scores_year_month_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_month_admin_scores');
    }
};
