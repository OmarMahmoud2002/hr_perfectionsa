<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('file_name', 255)->comment('اسم الملف الأصلي');
            $table->string('file_path', 500)->comment('مسار الملف المخزن');
            $table->tinyInteger('month')->unsigned()->comment('الشهر (1-12)');
            $table->smallInteger('year')->unsigned()->comment('السنة');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending')->comment('حالة المعالجة');
            $table->unsignedInteger('records_count')->default(0)->comment('عدد السجلات');
            $table->unsignedInteger('employees_count')->default(0)->comment('عدد الموظفين');
            $table->text('error_log')->nullable()->comment('سجل الأخطاء');
            $table->json('import_settings')->nullable()
                ->comment('إعدادات مخصصة لهذا الشهر (تجاوز الإعدادات الافتراضية)');
            $table->foreignId('uploaded_by')->constrained('users')->comment('المستخدم الذي رفع الملف');
            $table->timestamps();

            $table->unique(['month', 'year'])->comment('منع تكرار رفع نفس الشهر');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
