<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeMonthTaskEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'evaluator_user_id',
        'score',
        'note',
    ];

    protected $casts = [
        'task_id'          => 'integer',
        'evaluator_user_id'=> 'integer',
        'score'            => 'float',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(EmployeeMonthTask::class, 'task_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_user_id');
    }
}
