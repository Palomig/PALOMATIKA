<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGift extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'gift_type',
        'payload',
        'shown_at',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'shown_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
