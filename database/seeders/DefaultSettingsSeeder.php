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

            // Employee of Month defaults
            [
                'key'   => 'employee_of_month.weights.vote',
                'value' => '0.25',
                'group' => 'employee_of_month',
            ],
            [
                'key'   => 'employee_of_month.weights.admin',
                'value' => '0.00',
                'group' => 'employee_of_month',
            ],
            [
                'key'   => 'employee_of_month.weights.work_hours',
                'value' => '0.20',
                'group' => 'employee_of_month',
            ],
            [
                'key'   => 'employee_of_month.weights.punctuality',
                'value' => '0.15',
                'group' => 'employee_of_month',
            ],
            [
                'key'   => 'employee_of_month.weights.overtime',
                'value' => '0.00',
                'group' => 'employee_of_month',
            ],
            [
                'key'   => 'employee_of_month.weights.tasks',
                'value' => '0.40',
                'group' => 'employee_of_month',
            ],
            [
                'key'   => 'employee_of_month.formula_version',
                'value' => 'v2_tasks',
                'group' => 'employee_of_month',
            ],
            [
                'key'   => 'employee_of_month.criteria',
                'value' => '[{"key":"tasks","enabled":true},{"key":"punctuality","enabled":true},{"key":"work_hours","enabled":true},{"key":"vote","enabled":true}]',
                'group' => 'employee_of_month',
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
