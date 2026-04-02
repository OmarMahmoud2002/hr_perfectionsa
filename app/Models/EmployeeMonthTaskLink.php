<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeMonthTaskLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'url',
        'label',
    ];

    protected $casts = [
        'task_id' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(EmployeeMonthTask::class, 'task_id');
    }
}

