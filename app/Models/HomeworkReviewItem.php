<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Пункт разбора — задача домашки, которую учитель отметил «разобрать на уроке».
 *
 * Живёт отдельно от student_notes сознательно: заметка про ученика («не видит
 * подобия») остаётся в его карточке навсегда, а пункт разбора гаснет после
 * ближайшего урока. Смешать — значит либо засорить карточку, либо чистить руками.
 *
 * Статусы: pending — отмечено на проверке; planned — добавлено в конкретный
 * урок; done — этот урок завершён (проставляет LessonSessionService::end()).
 */
class HomeworkReviewItem extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PLANNED = 'planned';
    public const STATUS_DONE    = 'done';

    /** Статусы, в которых пункт считается живым: второй такой же не заводим. */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_PLANNED];

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'homework_assignment_id',
        'homework_topic_task_id',
        'note',
        'student_note_id',
        'status',
        'lesson_session_id',
        'resolved_at',
    ];

    protected $casts = [
        'created_at'  => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(HomeworkAssignment::class, 'homework_assignment_id');
    }

    public function topicTask(): BelongsTo
    {
        return $this->belongsTo(HomeworkTopicTask::class, 'homework_topic_task_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereIn('status', self::ACTIVE_STATUSES);
    }
}
