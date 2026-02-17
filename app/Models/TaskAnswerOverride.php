<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskAnswerOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_key',
        'answer',
        'source',
        'updated_by_user_id',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TaskAnswerOverrideLog::class, 'override_id');
    }
}
