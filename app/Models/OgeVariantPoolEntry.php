<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OgeVariantPoolEntry extends Model
{
    protected $table = 'oge_variant_pool';
    public $timestamps = false;

    protected $fillable = [
        'variant_id',
        'exam_type',
        'type',
        'status',
        'task_fingerprint',
        'deactivated_at',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(OgeVariant::class, 'variant_id');
    }

    public function poolTasks(): HasMany
    {
        return $this->hasMany(OgeVariantPoolTask::class, 'pool_id')->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForExamType(Builder $query, string $examType): Builder
    {
        return $query->where('exam_type', $examType);
    }

    public function deactivate(): void
    {
        $this->update([
            'status' => 'deactivated',
            'deactivated_at' => now(),
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'deactivated_at' => null,
        ]);
    }
}
