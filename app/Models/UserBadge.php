<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBadge extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'badge_id',
        'earned_at',
        'is_showcased',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'is_showcased' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }
}
