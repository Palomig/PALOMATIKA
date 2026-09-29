<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Доля выплаты одного пригласившего за одного друга. */
class FriendInviteCredit extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['invite_id', 'referrer_id', 'amount', 'paid_at', 'paid_by'];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function invite(): BelongsTo
    {
        return $this->belongsTo(FriendInvite::class, 'invite_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }
}
