<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAnswerOverrideLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'override_id',
        'old_answer',
        'new_answer',
        'changed_by_user_id',
    ];

    public function override(): BelongsTo
    {
        return $this->belongsTo(TaskAnswerOverride::class, 'override_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
