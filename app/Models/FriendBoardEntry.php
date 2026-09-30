<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Строка доски зовущих — ведёт супер-админ вручную, своя у каждой акции. */
class FriendBoardEntry extends Model
{
    protected $fillable = ['program', 'user_id', 'friends'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
