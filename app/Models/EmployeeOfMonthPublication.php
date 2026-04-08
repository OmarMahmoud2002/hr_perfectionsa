<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeOfMonthPublication extends Model
{
    use HasFactory;

    protected $fillable = [
        'month',
        'year',
        'published_at',
        'published_by_user_id',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'published_at' => 'datetime',
        'published_by_user_id' => 'integer',
    ];

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }
}
