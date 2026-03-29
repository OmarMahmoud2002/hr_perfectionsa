<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyPerformanceAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'file_size',
        'is_image',
    ];

    protected $casts = [
        'entry_id' => 'integer',
        'file_size' => 'integer',
        'is_image' => 'boolean',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(DailyPerformanceEntry::class, 'entry_id');
    }
}
