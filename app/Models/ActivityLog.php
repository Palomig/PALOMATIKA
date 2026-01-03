<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'activity_log';

    protected $fillable = [
        'user_id',
        'event_type',
        'page_url',
        'task_id',
        'metadata',
        'ip_address',
        'user_agent',
        'device_type',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public static function log(string $eventType, ?int $userId = null, array $data = []): self
    {
        return static::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'page_url' => $data['page_url'] ?? null,
            'task_id' => $data['task_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'device_type' => $data['device_type'] ?? null,
        ]);
    }
}
