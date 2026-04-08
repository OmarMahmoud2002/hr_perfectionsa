<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $titles = [
            ['key' => 'designer', 'name_ar' => 'مصمم', 'system_role_mapping' => 'employee'],
            ['key' => 'three_d', 'name_ar' => '3D', 'system_role_mapping' => 'employee'],
            ['key' => 'customer_service', 'name_ar' => 'خدمة عملاء', 'system_role_mapping' => 'employee'],
            ['key' => 'developer', 'name_ar' => 'مبرمج', 'system_role_mapping' => 'employee'],
            ['key' => 'evaluator', 'name_ar' => 'User', 'system_role_mapping' => 'user'],
            ['key' => 'hr', 'name_ar' => 'HR', 'system_role_mapping' => 'hr'],
            ['key' => 'admin', 'name_ar' => 'Admin', 'system_role_mapping' => 'admin'],
            ['key' => 'manager', 'name_ar' => 'مدير', 'system_role_mapping' => 'manager'],
            ['key' => 'office_girl', 'name_ar' => 'Office Girl', 'system_role_mapping' => 'office_girl'],
            ['key' => 'accountant', 'name_ar' => 'محاسب', 'system_role_mapping' => 'employee'],
        ];

        foreach ($titles as $title) {
            DB::table('job_titles')->updateOrInsert(
                ['key' => $title['key']],
                [
                    'name_ar' => $title['name_ar'],
                    'system_role_mapping' => $title['system_role_mapping'],
                    'is_system' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $titleIds = DB::table('job_titles')->pluck('id', 'key');

        DB::table('employees')
            ->whereNotNull('job_title')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($titleIds): void {
                foreach ($rows as $row) {
                    $key = (string) $row->job_title;
                    $jobTitleId = $titleIds[$key] ?? null;

                    if ($jobTitleId !== null) {
                        DB::table('employees')
                            ->where('id', $row->id)
                            ->update(['job_title_id' => $jobTitleId]);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('employees')->update(['job_title_id' => null]);

        DB::table('job_titles')
            ->where('is_system', true)
            ->whereIn('key', [
                'designer',
                'three_d',
                'customer_service',
                'developer',
                'evaluator',
                'hr',
                'admin',
                'manager',
                'office_girl',
                'accountant',
            ])
            ->delete();
    }
};
