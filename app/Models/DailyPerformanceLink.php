<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyPerformanceLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_id',
        'url',
    ];

    protected $casts = [
        'entry_id' => 'integer',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(DailyPerformanceEntry::class, 'entry_id');
    }
}
