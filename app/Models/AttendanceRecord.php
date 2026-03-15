<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;
    // ⚠️ لا يدعم Soft Delete - الحذف دائماً حقيقي (Hard Delete)

    protected $fillable = [
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'is_absent',
        'late_minutes',
        'overtime_minutes',
        'work_minutes',
        'notes',
        'import_batch_id',
    ];

    protected $casts = [
        'date'             => 'date',
        'is_absent'        => 'boolean',
        'late_minutes'     => 'integer',
        'overtime_minutes' => 'integer',
        'work_minutes'     => 'integer',
    ];

    // ========================
    // Relationships
    // ========================

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    // ========================
    // Accessors / Helpers
    // ========================

    /**
     * إجمالي ساعات التأخير (بدلاً من الدقائق)
     */
    public function getLateHoursAttribute(): float
    {
        return round($this->late_minutes / 60, 2);
    }

    /**
     * إجمالي ساعات Overtime
     */
    public function getOvertimeHoursAttribute(): float
    {
        return round($this->overtime_minutes / 60, 2);
    }

    /**
     * إجمالي ساعات العمل الفعلية
     */
    public function getWorkHoursAttribute(): float
    {
        return round($this->work_minutes / 60, 2);
    }
}
