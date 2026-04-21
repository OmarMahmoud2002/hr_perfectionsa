<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('daily_performance_links')) {
            return;
        }

        Schema::create('daily_performance_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')
                ->constrained('daily_performance_entries')
                ->cascadeOnDelete();
            $table->string('url', 2048);
            $table->timestamps();

            $table->index('entry_id', 'daily_performance_links_entry_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_performance_links');
    }
};
