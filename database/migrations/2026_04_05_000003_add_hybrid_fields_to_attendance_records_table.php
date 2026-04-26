<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
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

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        $this->rebuildAttendanceRecordsTable(importBatchNullable: true, includeHybridFields: true);
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
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

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        $this->rebuildAttendanceRecordsTable(importBatchNullable: false, includeHybridFields: false);
    }

    private function rebuildAttendanceRecordsTable(bool $importBatchNullable, bool $includeHybridFields): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        Schema::rename('attendance_records', 'attendance_records_old');
        DB::statement('DROP INDEX IF EXISTS attendance_records_employee_id_date_unique');
        DB::statement('DROP INDEX IF EXISTS attendance_records_date_index');
        DB::statement('DROP INDEX IF EXISTS attendance_records_import_batch_id_index');

        Schema::create('attendance_records', function (Blueprint $table) use ($importBatchNullable, $includeHybridFields) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->unsignedInteger('work_minutes')->default(0);
            $table->string('notes', 500)->nullable();
            $table->string('manual_status', 30)->nullable();
            $table->foreignId('import_batch_id')
                ->nullable($importBatchNullable)
                ->constrained('import_batches')
                ->cascadeOnDelete();

            if ($includeHybridFields) {
                $table->enum('source', ['excel', 'system'])->default('excel');
                $table->enum('type', ['office', 'remote'])->default('office');
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('device_info')->nullable();
                $table->string('photo_path', 500)->nullable();
            }

            $table->timestamps();
            $table->unique(['employee_id', 'date']);
            $table->index('date');
            $table->index('import_batch_id');
        });

        if ($includeHybridFields) {
            DB::statement(
                'INSERT INTO attendance_records (
                    id, employee_id, date, clock_in, clock_out, is_absent, late_minutes, overtime_minutes,
                    work_minutes, notes, manual_status, import_batch_id, created_at, updated_at
                )
                SELECT
                    id, employee_id, date, clock_in, clock_out, is_absent, late_minutes, overtime_minutes,
                    work_minutes, notes, manual_status, import_batch_id, created_at, updated_at
                FROM attendance_records_old'
            );
        } else {
            DB::statement(
                'INSERT INTO attendance_records (
                    id, employee_id, date, clock_in, clock_out, is_absent, late_minutes, overtime_minutes,
                    work_minutes, notes, manual_status, import_batch_id, created_at, updated_at
                )
                SELECT
                    id, employee_id, date, clock_in, clock_out, is_absent, late_minutes, overtime_minutes,
                    work_minutes, notes, manual_status, import_batch_id, created_at, updated_at
                FROM attendance_records_old
                WHERE import_batch_id IS NOT NULL'
            );
        }

        Schema::drop('attendance_records_old');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
