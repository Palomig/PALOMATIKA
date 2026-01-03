<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PuzzleTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'steps_json',
    ];

    protected $casts = [
        'steps_json' => 'array',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
