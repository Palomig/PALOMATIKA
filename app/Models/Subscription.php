<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'plan',
        'plan_period',
        'has_ai_addon',
        'amount',
        'teacher_commission',
        'referred_by_user_id',
        'starts_at',
        'ends_at',
        'status',
        'cancelled_at',
        'payment_provider',
        'payment_id',
    ];

    protected $casts = [
        'has_ai_addon' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }

    public function getAmountInRublesAttribute(): float
    {
        return $this->amount / 100;
    }

    public function getTeacherCommissionInRublesAttribute(): float
    {
        return $this->teacher_commission / 100;
    }

    public function getPlanLabelAttribute(): string
    {
        return match ($this->plan) {
            'start' => 'Старт',
            'standard' => 'Стандарт',
            'premium' => 'Премиум',
            default => $this->plan,
        };
    }

    public function getPeriodLabelAttribute(): string
    {
        return match ($this->plan_period) {
            'monthly' => 'Месяц',
            'until_oge' => 'До ОГЭ',
            default => $this->plan_period,
        };
    }
}
