<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            // حالة يدوية تتجاوز الحالة المحسوبة تلقائياً
            // القيم المسموحة: present | absent | weekly_leave | public_holiday | null
            $table->string('manual_status', 30)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn('manual_status');
        });
    }
};
