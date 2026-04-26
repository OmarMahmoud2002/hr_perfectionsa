<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable()->change();
            });

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        // SQLite test runs create the column as nullable from the start to avoid
        // rebuilding the users table after multiple foreign keys already exist.
    }

    public function down(): void
    {
        DB::table('users')
            ->whereNull('email')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['email' => 'user' . $user->id . '@placeholder.local']);
                }
            });

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable(false)->change();
            });

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }
    }
};
