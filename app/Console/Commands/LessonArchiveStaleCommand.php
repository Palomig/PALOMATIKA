<?php

namespace App\Console\Commands;

use App\Models\LessonSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LessonArchiveStaleCommand extends Command
{
    protected $signature = 'lesson:archive-stale
                            {--keep= : Comma-separated session IDs to preserve (default: none)}
                            {--dry-run : Show what would be closed without making changes}';

    protected $description = 'Завершает все незакрытые (draft/live) сессии, кроме указанных IDs.';

    public function handle(): int
    {
        $keepRaw = (string) ($this->option('keep') ?? '');
        $keepIds = array_filter(array_map('intval', explode(',', $keepRaw)));

        $query = LessonSession::whereIn('status', [LessonSession::STATUS_DRAFT, LessonSession::STATUS_LIVE]);
        if (!empty($keepIds)) {
            $query->whereNotIn('id', $keepIds);
        }

        $sessions = $query->get(['id', 'status', 'join_code', 'starts_at']);

        if ($sessions->isEmpty()) {
            $this->info('Нет незакрытых сессий для архивации.');
            return self::SUCCESS;
        }

        $this->table(['id', 'status', 'join_code', 'starts_at'], $sessions->map(fn ($s) => [
            $s->id, $s->status, $s->join_code ?? '—', $s->starts_at?->format('Y-m-d H:i') ?? '—',
        ])->all());

        if ($this->option('dry-run')) {
            $this->warn("dry-run: {$sessions->count()} сессий будет архивировано (ничего не изменено).");
            return self::SUCCESS;
        }

        $ids = $sessions->pluck('id')->all();
        DB::table('lesson_sessions')
            ->whereIn('id', $ids)
            ->update(['status' => LessonSession::STATUS_ENDED, 'ends_at' => now(), 'updated_at' => now()]);

        $this->info("Архивировано {$sessions->count()} сессий." . (!empty($keepIds) ? ' Сохранены IDs: ' . implode(', ', $keepIds) : ''));
        return self::SUCCESS;
    }
}
