<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Друг, которого привели на бесплатное первое занятие («Позови друга»). */
class FriendInvite extends Model
{
    protected $fillable = [
        'invitee_name',
        'invitee_grade',
        'first_lesson_on',
        'registered_by',
        'qualified_at',
        'qualified_by',
        'cancelled_at',
    ];

    protected $casts = [
        'first_lesson_on' => 'date',
        'qualified_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function credits(): HasMany
    {
        return $this->hasMany(FriendInviteCredit::class, 'invite_id');
    }

    public function isPending(): bool
    {
        return $this->qualified_at === null && $this->cancelled_at === null;
    }
}
