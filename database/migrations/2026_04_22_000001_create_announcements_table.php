<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('message');
            $table->string('link_url', 2048)->nullable();
            $table->string('image_path')->nullable();
            $table->string('audience_type', 32);
            $table->json('audience_meta')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
