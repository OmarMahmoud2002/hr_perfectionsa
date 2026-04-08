<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','manager','hr','employee','user','office_girl','department_manager') NOT NULL DEFAULT 'employee'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        DB::table('users')
            ->where('role', 'department_manager')
            ->update(['role' => 'employee']);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','manager','hr','employee','user','office_girl') NOT NULL DEFAULT 'employee'");
        }
    }
};
