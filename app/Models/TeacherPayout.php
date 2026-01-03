<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherPayout extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'teacher_id',
        'amount',
        'status',
        'payment_method',
        'payment_details',
        'requested_at',
        'processed_at',
    ];

    protected $casts = [
        'payment_details' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayoutItem::class, 'payout_id');
    }

    public function getAmountInRublesAttribute(): float
    {
        return $this->amount / 100;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
