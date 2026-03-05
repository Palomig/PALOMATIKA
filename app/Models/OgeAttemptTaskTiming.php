<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OgeAttemptTaskTiming extends Model
{
    protected $fillable = [
        'attempt_id',
        'task_number',
        'active_ms',
        'focus_count',
        'heartbeat_count',
        'blur_count',
        'first_focused_at',
        'last_focus_at',
        'last_heartbeat_at',
    ];

    protected $casts = [
        'first_focused_at' => 'datetime',
        'last_focus_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OgeAttempt::class, 'attempt_id');
    }
}
