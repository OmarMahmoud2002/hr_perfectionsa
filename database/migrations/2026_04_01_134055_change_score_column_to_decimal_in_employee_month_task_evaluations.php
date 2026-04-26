<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->changeScoreColumn(toDecimal: true);
    }

    public function down(): void
    {
        $this->changeScoreColumn(toDecimal: false);
    }

    private function changeScoreColumn(bool $toDecimal): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table('employee_month_task_evaluations', function (Blueprint $table) use ($toDecimal) {
                if ($toDecimal) {
                    $table->decimal('score', 4, 1)->change();

                    return;
                }

                $table->unsignedTinyInteger('score')->change();
            });

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        Schema::rename('employee_month_task_evaluations', 'employee_month_task_evaluations_old');
        DB::statement('DROP INDEX IF EXISTS employee_month_task_evaluations_task_unique');
        DB::statement('DROP INDEX IF EXISTS employee_month_task_evaluations_score_index');

        Schema::create('employee_month_task_evaluations', function (Blueprint $table) use ($toDecimal) {
            $table->id();
            $table->foreignId('task_id')
                ->constrained('employee_month_tasks')
                ->cascadeOnDelete();
            $table->foreignId('evaluator_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $toDecimal
                ? $table->decimal('score', 4, 1)
                : $table->unsignedTinyInteger('score');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique('task_id', 'employee_month_task_evaluations_task_unique');
            $table->index('score', 'employee_month_task_evaluations_score_index');
        });

        DB::statement(
            'INSERT INTO employee_month_task_evaluations (id, task_id, evaluator_user_id, score, note, created_at, updated_at)
             SELECT id, task_id, evaluator_user_id, score, note, created_at, updated_at
             FROM employee_month_task_evaluations_old'
        );

        Schema::drop('employee_month_task_evaluations_old');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
