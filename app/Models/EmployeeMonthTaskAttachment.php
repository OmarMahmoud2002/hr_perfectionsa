<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeMonthTaskAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'file_size',
        'is_image',
    ];

    protected $casts = [
        'task_id'   => 'integer',
        'file_size' => 'integer',
        'is_image'  => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(EmployeeMonthTask::class, 'task_id');
    }
}

