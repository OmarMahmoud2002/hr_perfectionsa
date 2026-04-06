<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->foreignId('import_batch_id')->nullable()->change();

            $table->enum('source', ['excel', 'system'])->default('excel')->after('import_batch_id');
            $table->enum('type', ['office', 'remote'])->default('office')->after('source');
            $table->decimal('latitude', 10, 7)->nullable()->after('type');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('ip_address', 45)->nullable()->after('longitude');
            $table->text('device_info')->nullable()->after('ip_address');
            $table->string('photo_path', 500)->nullable()->after('device_info');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn([
                'source',
                'type',
                'latitude',
                'longitude',
                'ip_address',
                'device_info',
                'photo_path',
            ]);

            $table->foreignId('import_batch_id')->nullable(false)->change();
        });
    }
};
