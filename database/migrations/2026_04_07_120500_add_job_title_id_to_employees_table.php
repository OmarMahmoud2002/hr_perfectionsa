<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('job_title_id')
                ->nullable()
                ->after('job_title')
                ->constrained('job_titles')
                ->nullOnDelete();

            $table->index('job_title_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['job_title_id']);
            $table->dropConstrainedForeignId('job_title_id');
        });
    }
};
