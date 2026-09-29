<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Бонус за каждые 6000 ₽ заработанного. */
class FriendInviteBonus extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['referrer_id', 'threshold', 'amount', 'paid_at', 'paid_by'];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }
}
