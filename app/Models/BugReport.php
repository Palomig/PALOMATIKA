<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugReport extends Model
{
    protected $fillable = [
        'user_id', 'url', 'route_name', 'description',
        'user_agent', 'screen_info', 'js_errors', 'ip',
    ];

    protected $casts = [
        'screen_info' => 'array',
        'js_errors'   => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
