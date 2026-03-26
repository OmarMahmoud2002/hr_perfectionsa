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
            ['email' => 'admin@perfection.com'],
            [
                'name'     => 'System Admin',
                'email'    => 'admin@perfection.com',
                'password' => Hash::make('123456789'),
                'role'     => 'admin',
            ]
        );

        $this->command->info('Admin user created: admin@perfection.com / 123456789');
    }
}
