<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'category',
        'oge_numbers',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'oge_numbers' => 'array',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Skill::class, 'parent_id');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_dependencies', 'skill_id', 'requires_skill_id')
            ->withPivot('min_weight');
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_dependencies', 'requires_skill_id', 'skill_id')
            ->withPivot('min_weight');
    }

    public function userSkills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_skills')
            ->withPivot('relevance');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
