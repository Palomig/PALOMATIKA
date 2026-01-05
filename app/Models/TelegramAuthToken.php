<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramAuthToken extends Model
{
    protected $fillable = [
        'token',
        'telegram_id',
        'first_name',
        'last_name',
        'username',
        'photo_url',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    public function isAuthenticated(): bool
    {
        return $this->status === 'authenticated' && !$this->isExpired();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function scopeAuthenticated($query)
    {
        return $query->where('status', 'authenticated')
            ->where('expires_at', '>', now());
    }
}
