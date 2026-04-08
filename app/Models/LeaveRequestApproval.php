<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'actor_user_id',
        'actor_role',
        'decision',
        'approved_days',
        'note',
        'decided_at',
    ];

    protected $casts = [
        'approved_days' => 'integer',
        'decided_at' => 'datetime',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
