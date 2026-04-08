<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('settings')->updateOrInsert(
            ['key' => 'default_required_work_days_before_leave'],
            [
                'value' => '120',
                'group' => 'leave',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('settings')->updateOrInsert(
            ['key' => 'default_annual_leave_days'],
            [
                'value' => '21',
                'group' => 'leave',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', [
                'default_required_work_days_before_leave',
                'default_annual_leave_days',
            ])
            ->delete();
    }
};
