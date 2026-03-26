<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeOfMonthResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'final_score',
        'breakdown',
        'formula_version',
        'generated_at',
    ];

    protected $casts = [
        'employee_id'    => 'integer',
        'month'          => 'integer',
        'year'           => 'integer',
        'final_score'    => 'decimal:2',
        'breakdown'      => 'array',
        'generated_at'   => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
