<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','manager','hr','employee','user','office_girl') NOT NULL DEFAULT 'employee'");
        }
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'office_girl')
            ->update(['role' => 'employee']);

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','manager','hr','employee','user') NOT NULL DEFAULT 'employee'");
        }
    }
};

