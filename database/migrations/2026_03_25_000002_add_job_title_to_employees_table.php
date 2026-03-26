<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Stored as string to allow adding new enum values in code without altering DB enum every time.
            $table->string('job_title', 50)
                ->nullable()
                ->after('name')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['job_title']);
            $table->dropColumn('job_title');
        });
    }
};
