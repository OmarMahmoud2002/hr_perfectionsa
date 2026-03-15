<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ac_no',
        'name',
        'basic_salary',
        'is_active',
        'work_start_time',
        'work_end_time',
        'overtime_start_time',
        'late_grace_minutes',
    ];

    protected $casts = [
        'basic_salary'       => 'decimal:2',
        'is_active'          => 'boolean',
        'late_grace_minutes' => 'integer',
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
}
