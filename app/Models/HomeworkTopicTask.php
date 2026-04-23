<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomeworkTopicTask extends Model
{
    protected $fillable = [
        'homework_id',
        'topic_number',
        'task_order',
        'task_payload',
        'correct_answer',
    ];

    protected $casts = [
        'task_payload' => 'array',
    ];

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(HomeworkTopicTaskSubmission::class);
    }
}
