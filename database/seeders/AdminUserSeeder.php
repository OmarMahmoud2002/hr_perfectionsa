<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء مستخدم Admin افتراضي
        User::updateOrCreate(
            ['email' => 'admin@attendance.com'],
            [
                'name'     => 'System Admin',
                'email'    => 'admin@attendance.com',
                'password' => Hash::make('Admin@123'),
                'role'     => 'admin',
            ]
        );

        $this->command->info('✅ Admin user created: admin@attendance.com / Admin@123');
    }
}
