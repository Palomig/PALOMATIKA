<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    protected $fillable = [
        'topic_id',
        'external_id',
        'text',
        'text_html',
        'image_path',
        'subtopic',
        'difficulty',
        'correct_answer',
        'answer_type',
        'puzzle_template_id',
        'times_shown',
        'times_correct',
        'avg_time_seconds',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function puzzleTemplate(): BelongsTo
    {
        return $this->belongsTo(PuzzleTemplate::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'task_skills')
            ->withPivot('relevance');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TaskStep::class)->orderBy('step_number');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDifficulty($query, int $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByTopic($query, int $topicId)
    {
        return $query->where('topic_id', $topicId);
    }

    public function getSuccessRateAttribute(): ?float
    {
        if ($this->times_shown === 0) {
            return null;
        }
        return round($this->times_correct / $this->times_shown * 100, 1);
    }

    public function getDifficultyLabelAttribute(): string
    {
        return match ($this->difficulty) {
            1 => 'Очень легко',
            2 => 'Легко',
            3 => 'Средне',
            4 => 'Сложно',
            5 => 'Очень сложно',
            default => 'Неизвестно',
        };
    }
}
