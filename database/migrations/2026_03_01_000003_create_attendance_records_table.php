<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date')->comment('تاريخ اليوم');
            $table->time('clock_in')->nullable()->comment('وقت الحضور');
            $table->time('clock_out')->nullable()->comment('وقت الانصراف');
            $table->boolean('is_absent')->default(false)->comment('هل هو غياب');
            $table->unsignedInteger('late_minutes')->default(0)->comment('دقائق التأخير');
            $table->unsignedInteger('overtime_minutes')->default(0)->comment('دقائق الـ Overtime');
            $table->unsignedInteger('work_minutes')->default(0)->comment('إجمالي دقائق العمل');
            $table->string('notes', 500)->nullable()->comment('ملاحظات');
            $table->foreignId('import_batch_id')->constrained('import_batches')->onDelete('cascade');
            $table->timestamps();
            // ⚠️ بدون Soft Delete - الحذف حقيقي (Hard Delete)

            $table->unique(['employee_id', 'date'])->comment('منع تكرار سجل الحضور ليوم واحد');
            $table->index('date');
            $table->index('import_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
