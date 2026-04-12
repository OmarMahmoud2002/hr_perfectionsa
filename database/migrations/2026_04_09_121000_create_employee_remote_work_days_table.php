<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_remote_work_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->timestamps();

            $table->unique(['employee_id', 'work_date'], 'employee_remote_work_days_employee_date_unique');
            $table->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_remote_work_days');
    }
};
