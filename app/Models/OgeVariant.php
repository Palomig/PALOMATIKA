<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OgeVariant extends Model
{
    use HasFactory;
    public const SOURCE_GENERATOR = 'generator';
    public const SOURCE_CUSTOM_RANDOM = 'custom_random';
    public const SOURCE_MINIAPP = 'miniapp';
    public const SOURCE_CURATED = 'curated';

    public const MODE_FULL = 'full';
    public const MODE_MINI_ALGEBRA = 'mini_algebra';
    public const MODE_MINI_GEOMETRY = 'mini_geometry';
    public const MODE_MINI_MIXED = 'mini_mixed';
    public const MODE_FULL_WITH_PART2 = 'full_with_part2';
    public const MODE_MINI_PART2 = 'mini_part2';

    public const EXAM_OGE  = 'oge';
    public const EXAM_VPR5 = 'vpr_5';
    public const EXAM_VPR6 = 'vpr_6';
    public const EXAM_VPR7 = 'vpr_7';
    public const EXAM_VPR8 = 'vpr_8';
    public const EXAM_EGE  = 'ege';
    public const EXAM_ENTRANCE10 = 'entrance10';

    protected $fillable = [
        'hash',
        'exam_type',
        'level',
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
