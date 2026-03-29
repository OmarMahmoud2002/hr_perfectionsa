<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyPerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_id',
        'reviewer_user_id',
        'rating',
        'comment',
        'reviewed_at',
    ];

    protected $casts = [
        'entry_id' => 'integer',
        'reviewer_user_id' => 'integer',
        'rating' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(DailyPerformanceEntry::class, 'entry_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
