<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('role')
                ->constrained('employees')
                ->nullOnDelete()
                ->unique();

            $table->boolean('must_change_password')
                ->default(false)
                ->after('password');

            $table->timestamp('last_password_changed_at')
                ->nullable()
                ->after('must_change_password');
        });

        DB::table('users')
            ->where('role', 'viewer')
            ->update(['role' => 'employee']);

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','manager','hr','employee') NOT NULL DEFAULT 'employee'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        DB::table('users')
            ->whereIn('role', ['employee', 'manager', 'hr'])
            ->update(['role' => 'viewer']);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','viewer') NOT NULL DEFAULT 'viewer'");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
            $table->dropColumn('must_change_password');
            $table->dropColumn('last_password_changed_at');
        });
    }
};
