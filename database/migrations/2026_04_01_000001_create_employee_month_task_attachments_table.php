<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_month_task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                ->constrained('employee_month_tasks')
                ->cascadeOnDelete();
            $table->string('disk', 50)->default('public');
            $table->string('path', 500);
            $table->string('original_name');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('is_image')->default(false);
            $table->timestamps();

            $table->index('task_id', 'emta_task_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_month_task_attachments');
    }
};

