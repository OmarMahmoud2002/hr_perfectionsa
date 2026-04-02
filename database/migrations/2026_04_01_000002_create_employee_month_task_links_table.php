<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_month_task_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                ->constrained('employee_month_tasks')
                ->cascadeOnDelete();
            $table->string('url', 2000);
            $table->string('label', 255)->nullable();
            $table->timestamps();

            $table->index('task_id', 'emtl_task_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_month_task_links');
    }
};

