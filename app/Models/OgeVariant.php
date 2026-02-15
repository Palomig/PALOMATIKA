<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OgeVariant extends Model
{
    protected $fillable = [
        'hash',
        'owner_teacher_id',
        'title',
        'config_json',
    ];

    protected $casts = [
        'config_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ownerTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_teacher_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(OgeAttempt::class, 'variant_id');
    }
}

