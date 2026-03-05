<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTopicMastery extends Model
{
    protected $table = 'student_topic_mastery';

    protected $fillable = [
        'student_id',
        'topic_id',
        'task_type',
        'svg_type',
        'subtype',
        'section',
        'attempts_count',
        'correct_count',
        'total_active_ms',
        'avg_active_ms',
        'accuracy',
        'mastery_score',
        'recent_outcomes',
        'current_correct_streak',
        'current_incorrect_streak',
        'last_outcome',
        'last_attempted_at',
    ];

    protected $casts = [
        'accuracy' => 'float',
        'mastery_score' => 'float',
        'recent_outcomes' => 'array',
        'last_outcome' => 'boolean',
        'last_attempted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
