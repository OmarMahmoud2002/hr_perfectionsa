<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')
                ->constrained('daily_performance_entries')
                ->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['entry_id', 'reviewer_user_id'], 'daily_performance_reviews_entry_reviewer_unique');
            $table->index('entry_id', 'daily_performance_reviews_entry_id_index');
            $table->index('reviewer_user_id', 'daily_performance_reviews_reviewer_user_id_index');
            $table->index('rating', 'daily_performance_reviews_rating_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_performance_reviews');
    }
};
