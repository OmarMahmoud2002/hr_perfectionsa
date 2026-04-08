<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name_ar', 150);
            $table->string('system_role_mapping', 50)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'name_ar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_titles');
    }
};
