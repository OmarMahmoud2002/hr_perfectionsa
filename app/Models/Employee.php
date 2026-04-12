<?php

namespace App\Models;

use App\Enums\JobTitle as LegacyJobTitle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'job_title_id',
        'department_id',
        'is_department_manager',
        'basic_salary',
        'is_active',
        'is_remote_worker',
        'allow_remote_from_anywhere',
        'work_start_time',
        'work_end_time',
        'overtime_start_time',
        'late_grace_minutes',
    ];

    protected $casts = [
        'basic_salary'       => 'decimal:2',
        'is_active'          => 'boolean',
        'is_remote_worker'   => 'boolean',
        'allow_remote_from_anywhere' => 'boolean',
        'is_department_manager' => 'boolean',
        'late_grace_minutes' => 'integer',
        'job_title'          => LegacyJobTitle::class,
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

    public function remoteWorkDays(): HasMany
    {
        return $this->hasMany(EmployeeRemoteWorkDay::class, 'employee_id');
    }

    public function dailyPerformanceEntries(): HasMany
    {
        return $this->hasMany(DailyPerformanceEntry::class, 'employee_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function managedDepartment(): HasOne
    {
        return $this->hasOne(Department::class, 'manager_employee_id');
    }

    public function jobTitleRef(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function leaveProfile(): HasOne
    {
        return $this->hasOne(EmployeeLeaveProfile::class, 'employee_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'employee_id');
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
        if ($this->job_title instanceof \App\Enums\JobTitle) {
            return $this->job_title->label();
        }

        if ($this->relationLoaded('jobTitleRef')) {
            return $this->jobTitleRef?->name_ar;
        }

        if ($this->job_title_id !== null) {
            return $this->jobTitleRef()->value('name_ar');
        }

        return null;
    }

    public function getPositionLineAttribute(): string
    {
        $departmentName = $this->relationLoaded('department')
            ? $this->department?->name
            : ($this->department_id ? $this->department()->value('name') : null);

        if ($this->is_department_manager && $departmentName) {
            return 'مدير قسم '.$departmentName;
        }

        $jobLabel = $this->job_title_label ?? 'غير محدد';

        if ($departmentName) {
            return $jobLabel.' ('.$departmentName.')';
        }

        return $jobLabel;
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

        if ($role === 'department_manager') {
            return false;
        }

        if ($this->relationLoaded('jobTitleRef')) {
            $mappedRole = $this->jobTitleRef?->system_role_mapping;

            if (in_array($mappedRole, ['manager', 'hr'], true)) {
                return true;
            }
        }

        $jobTitle = $this->job_title?->value;
        return in_array($jobTitle, ['manager', 'hr'], true);
    }
}
