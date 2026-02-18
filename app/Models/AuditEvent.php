<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    protected $fillable = [
        'occurred_at',
        'event_type',
        'category',
        'severity',
        'actor_user_id',
        'actor_role',
        'subject_type',
        'subject_id',
        'request_id',
        'ip',
        'user_agent',
        'payload_json',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
