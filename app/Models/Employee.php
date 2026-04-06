<?php

namespace App\Models;

use App\Enums\JobTitle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ac_no',
        'name',
        'job_title',
        'basic_salary',
        'is_active',
        'is_remote_worker',
        'work_start_time',
        'work_end_time',
        'overtime_start_time',
        'late_grace_minutes',
    ];

    protected $casts = [
        'basic_salary'       => 'decimal:2',
        'is_active'          => 'boolean',
        'is_remote_worker'   => 'boolean',
        'late_grace_minutes' => 'integer',
        'job_title'          => JobTitle::class,
    ];

    // ========================
    // Relationships
    // ========================

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function payrollReports(): HasMany
    {
        return $this->hasMany(PayrollReport::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function monthVotesReceived(): HasMany
    {
        return $this->hasMany(EmployeeMonthVote::class, 'voted_employee_id');
    }

    public function monthAdminScores(): HasMany
    {
        return $this->hasMany(EmployeeMonthAdminScore::class);
    }

    public function monthResults(): HasMany
    {
        return $this->hasMany(EmployeeOfMonthResult::class);
    }

    public function monthTaskAssignments(): HasMany
    {
        return $this->hasMany(EmployeeMonthTaskAssignment::class, 'employee_id');
    }

    public function monthTasks(): BelongsToMany
    {
        return $this->belongsToMany(EmployeeMonthTask::class, 'employee_month_task_assignments', 'employee_id', 'task_id')
            ->withTimestamps();
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'employee_location')
            ->withTimestamps();
    }

    public function dailyPerformanceEntries(): HasMany
    {
        return $this->hasMany(DailyPerformanceEntry::class, 'employee_id');
    }

    // ========================
    // Scopes
    // ========================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ========================
    // Accessors / Helpers
    // ========================

    /**
     * يُرجع مصفوفة إعدادات الشيفت الخاصة بالموظف (القيم غير الـ null فقط)
     * تُستخدم لتجاوز الإعدادات الافتراضية أثناء حساب الحضور
     */
    public function getShiftOverrides(): array
    {
        return array_filter([
            'work_start_time'     => $this->work_start_time,
            'work_end_time'       => $this->work_end_time,
            'overtime_start_time' => $this->overtime_start_time,
            'late_grace_minutes'  => $this->late_grace_minutes,
        ], fn ($v) => $v !== null);
    }

    public function getJobTitleLabelAttribute(): ?string
    {
        return $this->job_title?->label();
    }

    /**
     * Admin-like roles (admin, manager, hr) are exempt from late/OT calculations.
     */
    public function isAdminLikeForAttendance(): bool
    {
        $role = $this->user?->role;
        if (in_array($role, ['admin', 'manager', 'hr'], true)) {
            return true;
        }

        $jobTitle = $this->job_title?->value;
        return in_array($jobTitle, ['manager', 'hr'], true);
    }
}
