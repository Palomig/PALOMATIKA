<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'referral_code',
        'ip_address',
        'user_agent',
        'registered_user_id',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function registeredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_user_id');
    }

    public function scopeConverted($query)
    {
        return $query->whereNotNull('registered_user_id');
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('referral_code', $code);
    }

    public function isConverted(): bool
    {
        return $this->registered_user_id !== null;
    }
}
