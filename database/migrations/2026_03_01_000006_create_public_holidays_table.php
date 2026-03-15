<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->onDelete('cascade');
            $table->date('date')->comment('تاريخ الإجازة الرسمية');
            $table->string('name', 255)->comment('اسم الإجازة (عيد الفطر، عيد الأضحى...)');
            $table->timestamps();
            // ⚠️ بدون Soft Delete - الحذف حقيقي

            $table->index('import_batch_id');
            $table->index('date');
            $table->unique(['import_batch_id', 'date'])->comment('منع تكرار نفس التاريخ في نفس الدفعة');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
    }
};
