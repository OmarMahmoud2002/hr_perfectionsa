<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (!Schema::hasColumn('employees', 'allow_remote_from_anywhere')) {
                $table->boolean('allow_remote_from_anywhere')
                    ->default(false)
                    ->after('is_remote_worker');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (Schema::hasColumn('employees', 'allow_remote_from_anywhere')) {
                $table->dropColumn('allow_remote_from_anywhere');
            }
        });
    }
};
