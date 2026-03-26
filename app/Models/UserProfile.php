<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'avatar_path',
        'bio',
        'social_link_1',
        'social_link_2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
