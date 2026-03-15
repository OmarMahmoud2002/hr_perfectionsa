<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // حذف القيد الفريد على (month, year) لأنه يمنع رفع نفس الشهر مرة ثانية
        // سيُدار التحقق من التكرار على مستوى التطبيق بدلاً من قاعدة البيانات
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropUnique('import_batches_month_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->unique(['month', 'year'], 'import_batches_month_year_unique');
        });
    }
};
