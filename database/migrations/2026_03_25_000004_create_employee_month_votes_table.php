<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_month_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voter_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('voted_employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('vote_month');
            $table->unsignedSmallInteger('vote_year');
            $table->timestamps();

            $table->unique(['voter_user_id', 'vote_month', 'vote_year'], 'employee_month_votes_voter_month_year_unique');
            $table->index(['vote_year', 'vote_month', 'voted_employee_id'], 'employee_month_votes_year_month_employee_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_month_votes');
    }
};
