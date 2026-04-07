<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OgeAttempt extends Model
{
    use HasFactory;
    protected $fillable = [
        'variant_id',
        'student_id',
        'status',
        'device_meta',
        'frozen_answers_json',
        'started_at',
        'submitted_at',
        'last_seen_at',
    ];

    protected $casts = [
        'device_meta' => 'array',
        'frozen_answers_json' => 'array',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(OgeVariant::class, 'variant_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OgeAttemptEvent::class, 'attempt_id')->orderBy('seq');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(OgeAttemptAnswer::class, 'attempt_id');
    }

    public function taskTimings(): HasMany
    {
        return $this->hasMany(OgeAttemptTaskTiming::class, 'attempt_id');
    }

    public function scorings(): HasMany
    {
        return $this->hasMany(OgeAttemptScoring::class, 'attempt_id');
    }

    public function taskDetails(): HasMany
    {
        return $this->hasMany(OgeAttemptTaskDetail::class, 'attempt_id');
    }
}

