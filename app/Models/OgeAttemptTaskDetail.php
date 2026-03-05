<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OgeAttemptTaskDetail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attempt_id',
        'task_number',
        'topic_id',
        'block_number',
        'zadanie_number',
        'task_index',
        'task_type',
        'svg_type',
        'subtype',
        'section',
        'source',
        'task_key',
        'task_fingerprint',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OgeAttempt::class, 'attempt_id');
    }

    /**
     * Build a canonical task_key from components.
     */
    public static function buildTaskKey(string $topicId, int $blockNumber, int $zadanieNumber, ?int $taskIndex): string
    {
        $key = "topic_{$topicId}_block_{$blockNumber}_zadanie_{$zadanieNumber}";
        if ($taskIndex !== null) {
            $key .= "_task_{$taskIndex}";
        }
        return $key;
    }
}
