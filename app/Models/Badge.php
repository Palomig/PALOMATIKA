<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Badge extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'condition_type',
        'condition_value',
        'condition_json',
        'rarity',
        'is_active',
    ];

    protected $casts = [
        'condition_json' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('condition_type', $type);
    }

    public function getRarityColorAttribute(): string
    {
        return match ($this->rarity) {
            'legendary' => '#FFD700',
            'epic' => '#9B59B6',
            'rare' => '#3498DB',
            default => '#95A5A6',
        };
    }
}
