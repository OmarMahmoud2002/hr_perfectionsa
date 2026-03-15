<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // أوقات شيفت العمل الخاص بالموظف — null يعني استخدام الإعداد الافتراضي للنظام
            $table->string('work_start_time', 5)->nullable()->default(null)
                ->comment('وقت بدء العمل للموظف (HH:MM) — null = الافتراضي 09:00');
            $table->string('work_end_time', 5)->nullable()->default(null)
                ->comment('وقت انتهاء العمل للموظف (HH:MM) — null = الافتراضي 17:00');
            $table->string('overtime_start_time', 5)->nullable()->default(null)
                ->comment('وقت بدء الأوفرتايم (HH:MM) — null = الافتراضي 17:30');
            $table->unsignedSmallInteger('late_grace_minutes')->nullable()->default(null)
                ->comment('فترة السماح بالتأخير بالدقائق — null = الافتراضي 30');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['work_start_time', 'work_end_time', 'overtime_start_time', 'late_grace_minutes']);
        });
    }
};
