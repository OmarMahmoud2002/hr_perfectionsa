<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmployeeMonthTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'period_month',
        'period_year',
        'period_start_date',
        'period_end_date',
        'task_date',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'task_date' => 'date',
        'created_by' => 'integer',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeMonthTaskAssignment::class, 'task_id');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_month_task_assignments', 'task_id', 'employee_id')
            ->withTimestamps();
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(EmployeeMonthTaskEvaluation::class, 'task_id');
    }
}
