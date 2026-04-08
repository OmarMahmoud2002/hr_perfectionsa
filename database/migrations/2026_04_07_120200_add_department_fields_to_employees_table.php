<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('job_title')
                ->constrained('departments')
                ->nullOnDelete();

            $table->boolean('is_department_manager')
                ->default(false)
                ->after('department_id');

            $table->index(['department_id', 'is_department_manager'], 'employees_department_manager_idx');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_department_manager_idx');
            $table->dropColumn('is_department_manager');
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
