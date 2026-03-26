<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeMonthTaskAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'employee_id',
    ];

    protected $casts = [
        'task_id' => 'integer',
        'employee_id' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(EmployeeMonthTask::class, 'task_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
