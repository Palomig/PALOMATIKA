<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonAttendance extends Model
{
    protected $table = 'lesson_attendance';

    protected $fillable = [
        'student_id',
        'schedule_id',
        'lesson_date',
        'start_time',
        'status',
        'note',
        'source',
    ];

    protected $casts = [
        'lesson_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(LessonSchedule::class, 'schedule_id');
    }
}
