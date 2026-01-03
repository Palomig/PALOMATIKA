<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payout_id',
        'subscription_id',
        'amount',
    ];

    public function payout(): BelongsTo
    {
        return $this->belongsTo(TeacherPayout::class, 'payout_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function getAmountInRublesAttribute(): float
    {
        return $this->amount / 100;
    }
}
