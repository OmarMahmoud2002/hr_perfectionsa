<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_reports', function (Blueprint $table) {
            // عدد الأسابيع التي حضر فيها الموظف جميع أيام العمل (بدون أي غياب)
            $table->unsignedInteger('full_attendance_weeks')
                ->default(0)
                ->after('total_overtime_minutes')
                ->comment('عدد الأسابيع ذات الحضور الكامل (بدون أي غياب)');

            // قيمة بونص الحضور الكامل = full_attendance_weeks × يومية الموظف
            $table->decimal('attendance_bonus', 10, 2)
                ->default(0)
                ->after('overtime_bonus')
                ->comment('بونص الحضور الكامل الأسبوعي');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_reports', function (Blueprint $table) {
            $table->dropColumn(['full_attendance_weeks', 'attendance_bonus']);
        });
    }
};
