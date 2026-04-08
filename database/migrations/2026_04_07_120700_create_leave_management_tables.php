<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_leave_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->date('employment_start_date')->nullable();
            $table->unsignedSmallInteger('required_work_days_before_leave')->nullable();
            $table->unsignedSmallInteger('annual_leave_quota')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('manager_employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('requested_days');
            $table->text('reason')->nullable();

            $table->string('status', 40)->default('pending');
            $table->string('hr_status', 40)->default('pending');
            $table->string('manager_status', 40)->default('pending');

            $table->unsignedSmallInteger('hr_approved_days')->nullable();
            $table->unsignedSmallInteger('final_approved_days')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['department_id', 'status']);
            $table->index(['manager_employee_id', 'manager_status']);
            $table->index(['hr_status', 'manager_status']);
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('leave_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('actor_role', 40);
            $table->string('decision', 40);
            $table->unsignedSmallInteger('approved_days')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();

            $table->index(['leave_request_id', 'actor_role']);
            $table->index(['actor_user_id', 'decided_at']);
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('annual_quota_days');
            $table->unsignedSmallInteger('used_days')->default(0);
            $table->unsignedSmallInteger('remaining_days')->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'year']);
            $table->index(['year', 'remaining_days']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_request_approvals');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('employee_leave_profiles');
    }
};
