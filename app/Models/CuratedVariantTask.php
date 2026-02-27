<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuratedVariantTask extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'variant_id',
        'task_number',
        'topic_id',
        'block_number',
        'zadanie_number',
        'task_index',
        'sort_order',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(OgeVariant::class, 'variant_id');
    }
}
