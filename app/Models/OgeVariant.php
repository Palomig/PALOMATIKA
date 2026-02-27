<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OgeVariant extends Model
{
    public const SOURCE_GENERATOR = 'generator';
    public const SOURCE_CUSTOM_RANDOM = 'custom_random';
    public const SOURCE_MINIAPP = 'miniapp';
    public const SOURCE_CURATED = 'curated';

    public const MODE_FULL = 'full';
    public const MODE_MINI_ALGEBRA = 'mini_algebra';
    public const MODE_MINI_GEOMETRY = 'mini_geometry';
    public const MODE_MINI_MIXED = 'mini_mixed';

    protected $fillable = [
        'hash',
        'owner_teacher_id',
        'title',
        'source',
        'external_ref',
        'created_via',
        'config_json',
        'mode',
        'is_curated',
    ];

    protected $casts = [
        'config_json' => 'array',
        'is_curated' => 'boolean',
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

    public function curatedTasks(): HasMany
    {
        return $this->hasMany(CuratedVariantTask::class, 'variant_id')->orderBy('sort_order');
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
