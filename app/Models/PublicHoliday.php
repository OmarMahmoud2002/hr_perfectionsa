<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicHoliday extends Model
{
    use HasFactory;
    // ⚠️ بدون Soft Delete - الحذف دائماً حقيقي

    protected $fillable = [
        'import_batch_id',
        'date',
        'name',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // ========================
    // Relationships
    // ========================

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
