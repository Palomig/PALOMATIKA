<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkTopicTaskSubmission extends Model
{
    protected $fillable = [
        'homework_assignment_id',
        'homework_topic_task_id',
        'attempts_count',
        'first_answer',
        'second_answer',
        'is_correct',
        'solution_photo_path',
        'solution_photo_remote_id',
        'accepted_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'accepted_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(HomeworkAssignment::class, 'homework_assignment_id');
    }

    public function homeworkTopicTask(): BelongsTo
    {
        return $this->belongsTo(HomeworkTopicTask::class);
    }
}
