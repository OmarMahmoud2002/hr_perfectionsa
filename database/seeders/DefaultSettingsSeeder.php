<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class DefaultSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // مواعيد العمل
            [
                'key'   => 'work_start_time',
                'value' => '09:00',
                'group' => 'work_schedule',
            ],
            [
                'key'   => 'work_end_time',
                'value' => '17:00',
                'group' => 'work_schedule',
            ],
            [
                'key'   => 'overtime_start_time',
                'value' => '17:30',
                'group' => 'work_schedule',
            ],

            // الإعدادات المالية
            [
                'key'   => 'late_deduction_per_hour',
                'value' => '0',
                'group' => 'payroll',
            ],
            [
                'key'   => 'overtime_rate_per_hour',
                'value' => '0',
                'group' => 'payroll',
            ],
            [
                'key'   => 'absent_deduction_per_day',
                'value' => '0',
                'group' => 'payroll',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'group' => $setting['group']]
            );
        }

        $this->command->info('✅ Default settings seeded successfully.');
    }
}
