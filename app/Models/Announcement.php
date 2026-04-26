<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_user_id',
        'title',
        'message',
        'link_url',
        'image_path',
        'audience_type',
        'audience_meta',
        'recipient_count',
    ];

    protected $casts = [
        'audience_meta' => 'array',
        'recipient_count' => 'integer',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
