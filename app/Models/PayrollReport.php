<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'total_working_days',
        'total_present_days',
        'total_absent_days',
        'total_late_minutes',
        'total_overtime_minutes',
        'full_attendance_weeks',
        'basic_salary',
        'late_deduction',
        'absent_deduction',
        'overtime_bonus',
        'attendance_bonus',
        'extra_bonus',
        'extra_deduction',
        'adjustment_note',
        'net_salary',
        'net_salary_final',
        'is_locked',
    ];

    protected $casts = [
        'month'                  => 'integer',
        'year'                   => 'integer',
        'total_working_days'     => 'integer',
        'total_present_days'     => 'integer',
        'total_absent_days'      => 'integer',
        'total_late_minutes'     => 'integer',
        'total_overtime_minutes' => 'integer',
        'full_attendance_weeks'  => 'integer',
        'basic_salary'           => 'decimal:2',
        'late_deduction'         => 'decimal:2',
        'absent_deduction'       => 'decimal:2',
        'overtime_bonus'         => 'decimal:2',
        'attendance_bonus'       => 'decimal:2',
        'extra_bonus'            => 'decimal:2',
        'extra_deduction'        => 'decimal:2',
        'net_salary'             => 'decimal:2',
        'net_salary_final'       => 'decimal:2',
        'is_locked'              => 'boolean',
    ];

    // ========================
    // Boot Method - Auto-calculate net_salary_final
    // ========================

    protected static function boot()
    {
        parent::boot();

        // تلقائياً حساب net_salary_final عند الحفظ أو التحديث
        static::saving(function ($report) {
            $report->net_salary_final = max(
                0,
                (float) $report->net_salary + (float) $report->extra_bonus - (float) $report->extra_deduction
            );
        });
    }

    // ========================
    // Relationships
    // ========================

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    // ========================
    // Accessors / Helpers
    // ========================

    public function getTotalLateHoursAttribute(): float
    {
        return round($this->total_late_minutes / 60, 2);
    }

    public function getTotalOvertimeHoursAttribute(): float
    {
        return round($this->total_overtime_minutes / 60, 2);
    }

    public function getTotalDeductionsAttribute(): float
    {
        return $this->late_deduction + $this->absent_deduction;
    }

    /**
     * هل يوجد تسوية إضافية على هذا الكشف
     */
    public function getHasAdjustmentAttribute(): bool
    {
        return $this->extra_bonus > 0 || $this->extra_deduction > 0;
    }

    /**
     * اسم الشهر
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس',
            4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
            7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر',
            10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return $months[$this->month] ?? '';
    }
}
