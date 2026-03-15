<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('ac_no', 50)->unique()->comment('رقم الموظف في جهاز البصمة');
            $table->string('name', 255)->comment('اسم الموظف');
            $table->decimal('basic_salary', 10, 2)->default(0)->comment('المرتب الأساسي');
            $table->tinyInteger('day_off')->unsigned()->nullable()
                ->comment('يوم الإجازة الثاني: 0=أحد,1=اثنين,2=ثلاثاء,3=أربعاء,4=خميس,6=سبت');
            $table->boolean('is_active')->default(true)->comment('هل الموظف نشط');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
