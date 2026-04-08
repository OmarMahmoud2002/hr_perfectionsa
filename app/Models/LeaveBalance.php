<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'year',
        'annual_quota_days',
        'used_days',
        'remaining_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'annual_quota_days' => 'integer',
        'used_days' => 'integer',
        'remaining_days' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
