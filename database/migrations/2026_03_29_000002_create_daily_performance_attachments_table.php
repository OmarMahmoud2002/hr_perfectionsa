<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_performance_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')
                ->constrained('daily_performance_entries')
                ->cascadeOnDelete();
            $table->string('disk', 50)->default('public');
            $table->string('path', 500);
            $table->string('original_name');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('is_image')->default(false);
            $table->timestamps();

            $table->index('entry_id', 'daily_performance_attachments_entry_id_index');
            $table->index('is_image', 'daily_performance_attachments_is_image_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_performance_attachments');
    }
};
