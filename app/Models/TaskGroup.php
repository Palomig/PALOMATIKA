<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Задание (`zadanie`) внутри блока — то, что интерфейс рендерит целиком:
 * инструкция, тип и список задач под ней.
 */
class TaskGroup extends Model
{
    protected $fillable = [
        'bank', 'grade', 'topic', 'block_number', 'block_title',
        'zadanie_number', 'position', 'instruction', 'type', 'svg_type',
        'payload', 'status', 'source',
    ];

    protected $casts = [
        'payload' => 'array',
        'grade' => 'integer',
        'block_number' => 'integer',
        'zadanie_number' => 'integer',
        'position' => 'integer',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }
}
