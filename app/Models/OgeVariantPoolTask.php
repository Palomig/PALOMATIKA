<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OgeVariantPoolTask extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'pool_id',
        'topic_id',
        'block_number',
        'zadanie_number',
        'task_id',
        'sort_order',
    ];

    public function poolEntry(): BelongsTo
    {
        return $this->belongsTo(OgeVariantPoolEntry::class, 'pool_id');
    }

    /**
     * Get a unique reference string for this task.
     */
    public function toRef(): string
    {
        return "{$this->topic_id}_{$this->block_number}_{$this->zadanie_number}_{$this->task_id}";
    }
}
