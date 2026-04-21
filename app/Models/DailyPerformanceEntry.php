<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyPerformanceEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'work_date',
        'project_name',
        'work_description',
        'submitted_at',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'work_date' => 'date',
        'submitted_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DailyPerformanceAttachment::class, 'entry_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(DailyPerformanceLink::class, 'entry_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DailyPerformanceReview::class, 'entry_id');
    }
}
