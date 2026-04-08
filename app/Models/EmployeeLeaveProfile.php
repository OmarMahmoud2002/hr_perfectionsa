<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeaveProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'employment_start_date',
        'required_work_days_before_leave',
        'annual_leave_quota',
    ];

    protected $casts = [
        'employment_start_date' => 'date',
        'required_work_days_before_leave' => 'integer',
        'annual_leave_quota' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
