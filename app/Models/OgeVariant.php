<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OgeVariant extends Model
{
    public const SOURCE_GENERATOR = 'generator';
    public const SOURCE_CUSTOM_RANDOM = 'custom_random';

    protected $fillable = [
        'hash',
        'owner_teacher_id',
        'title',
        'source',
        'external_ref',
        'created_via',
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

    public function source(): string
    {
        $source = $this->attributes['source'] ?? ($this->config_json['source'] ?? self::SOURCE_GENERATOR);

        return is_string($source) && $source !== '' ? $source : self::SOURCE_GENERATOR;
    }

    public function isCustomRandom(): bool
    {
        return $this->source() === self::SOURCE_CUSTOM_RANDOM;
    }
}
