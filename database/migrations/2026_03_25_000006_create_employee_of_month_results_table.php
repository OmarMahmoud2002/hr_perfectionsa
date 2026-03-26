<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_of_month_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('final_score', 8, 2);
            $table->json('breakdown');
            $table->string('formula_version', 50)->default('v1');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year'], 'employee_of_month_results_employee_month_year_unique');
            $table->index(['year', 'month', 'final_score'], 'employee_of_month_results_year_month_score_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_of_month_results');
    }
};
