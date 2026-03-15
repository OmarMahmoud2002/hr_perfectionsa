<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->tinyInteger('month')->unsigned()->comment('الشهر');
            $table->smallInteger('year')->unsigned()->comment('السنة');
            $table->unsignedInteger('total_working_days')->default(0)->comment('إجمالي أيام العمل المفترضة');
            $table->unsignedInteger('total_present_days')->default(0)->comment('أيام الحضور');
            $table->unsignedInteger('total_absent_days')->default(0)->comment('أيام الغياب');
            $table->unsignedInteger('total_late_minutes')->default(0)->comment('إجمالي دقائق التأخير');
            $table->unsignedInteger('total_overtime_minutes')->default(0)->comment('إجمالي دقائق Overtime');
            $table->decimal('basic_salary', 10, 2)->default(0)->comment('المرتب الأساسي');
            $table->decimal('late_deduction', 10, 2)->default(0)->comment('خصم التأخير');
            $table->decimal('absent_deduction', 10, 2)->default(0)->comment('خصم الغياب');
            $table->decimal('overtime_bonus', 10, 2)->default(0)->comment('مكافأة Overtime');
            $table->decimal('net_salary', 10, 2)->default(0)->comment('المرتب النهائي');
            $table->boolean('is_locked')->default(false)->comment('تأمين الراتب');
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year'])->comment('منع تكرار كشف الراتب');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_reports');
    }
};
