<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Страница решения задачи. Решение на несколько страниц — несколько фото
 * (до 10 на попытку), поэтому они лежат отдельно от сабмишна.
 *
 * Заполнено ровно одно из двух: `remote_id` (сервис hw-photos на VPS, обычный путь)
 * или `path` (диск хостинга — фолбэк, когда сервис недоступен).
 */
class HomeworkSolutionPhoto extends Model
{
    public const MAX_PER_ATTEMPT = 10;

    public $timestamps = false;

    protected $fillable = [
        'submission_id',
        'attempt_no',
        'position',
        'remote_id',
        'path',
    ];

    protected $casts = [
        'attempt_no' => 'integer',
        'position' => 'integer',
        'created_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(HomeworkTopicTaskSubmission::class, 'submission_id');
    }

    public function isRemote(): bool
    {
        return trim((string) $this->remote_id) !== '';
    }
}
