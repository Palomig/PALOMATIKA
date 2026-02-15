<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OgeAttemptEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attempt_id',
        'seq',
        'event_type',
        'task_number',
        'payload_json',
        'client_ts',
        'server_ts',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'client_ts' => 'datetime',
        'server_ts' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OgeAttempt::class, 'attempt_id');
    }
}

