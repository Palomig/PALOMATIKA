<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OgeGeneratorTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'zadaniya_json',
    ];

    protected $casts = [
        'zadaniya_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
