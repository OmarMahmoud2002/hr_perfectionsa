<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'department_id',
        'manager_employee_id',
        'start_date',
        'end_date',
        'requested_days',
        'reason',
        'status',
        'hr_status',
        'manager_status',
        'hr_approved_days',
        'final_approved_days',
        'submitted_at',
        'finalized_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'requested_days' => 'integer',
        'hr_approved_days' => 'integer',
        'final_approved_days' => 'integer',
        'submitted_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function managerEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LeaveRequestApproval::class, 'leave_request_id');
    }
}
