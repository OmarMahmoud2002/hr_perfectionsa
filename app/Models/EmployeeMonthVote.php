<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeMonthVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'voter_user_id',
        'voted_employee_id',
        'vote_month',
        'vote_year',
    ];

    protected $casts = [
        'voter_user_id'    => 'integer',
        'voted_employee_id'=> 'integer',
        'vote_month'       => 'integer',
        'vote_year'        => 'integer',
    ];

    public function voter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voter_user_id');
    }

    public function votedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'voted_employee_id');
    }
}
