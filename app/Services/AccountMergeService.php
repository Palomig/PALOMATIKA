<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Склейка двух аккаунтов одного человека.
 *
 * Один человек мог завести несколько записей: вход через Яндекс/Google на сайте,
 * вход через мини-апп (настоящий telegram id) и вход через OIDC (псевдоним).
 * Признак «это один человек» — подтверждённый telegram chat_id: он приходит из
 * бота в сессии самого пользователя, поэтому совпадений «на глазок» тут нет.
 */
class AccountMergeService
{
    /**
     * Куда что переносить: таблица => колонки со ссылкой на users.id.
     * Только владение данными; служебные таблицы (sessions, audit) не трогаем.
     *
     * @var array<string, array<int, string>>
     */
    private const OWNERSHIP = [
        'activity_log'                 => ['user_id'],
        'bug_reports'                  => ['user_id'],
        'homeworks'                    => ['teacher_id'],
        'homework_assignments'         => ['student_id'],
        'jarvis_materials'             => ['owner_teacher_id'],
        'lesson_activity_intervals'    => ['student_id'],
        'lesson_attendance'            => ['student_id'],
        'lesson_behavior_events'       => ['student_id'],
        'lesson_schedule'              => ['student_id', 'teacher_id'],
        'lesson_sessions'              => ['teacher_id'],
        'lesson_session_attempts'      => ['student_id'],
        'lesson_session_participants'  => ['student_id', 'released_by'],
        'lesson_session_tasks'         => ['assigned_student_id'],
        'oge_attempts'                 => ['student_id'],
        'oge_generator_templates'      => ['user_id'],
        'oge_variants'                 => ['owner_teacher_id'],
        'parent_student'               => ['student_id', 'parent_id'],
        'practice_game_runs'           => ['user_id'],
        'referral_clicks'              => ['registered_user_id'],
        'star_transactions'            => ['user_id', 'related_user_id'],
        'student_groups'               => ['owner_teacher_id'],
        'student_group_members'        => ['student_id'],
        'student_notes'                => ['student_id', 'teacher_id'],
        'student_topic_mastery'        => ['student_id'],
        'task_answer_overrides'        => ['updated_by_user_id'],
        'task_answer_override_logs'    => ['changed_by_user_id'],
        'teacher_students'             => ['student_id', 'teacher_id'],
        'user_skills'                  => ['user_id'],
        'users'                        => ['referred_by_user_id'],
    ];

    /**
     * Пары, которые обязаны быть уникальными: после переноса могли бы задвоиться.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const UNIQUE_PAIRS = [
        'teacher_students'            => ['teacher_id', 'student_id'],
        'parent_student'              => ['parent_id', 'student_id'],
        'student_group_members'       => ['group_id', 'student_id'],
        'lesson_session_participants' => ['lesson_session_id', 'student_id'],
    ];

    /**
     * Какой из двух аккаунтов оставить живым.
     *
     * Учительские/админские права важнее возраста записи: потерять роль хуже,
     * чем сохранить более молодой аккаунт.
     */
    public function pickCanonical(User $a, User $b): User
    {
        $weight = fn (User $u) => match ($u->role) {
            'admin' => 3,
            'teacher' => 2,
            'parent' => 1,
            default => 0,
        };

        if ($weight($a) !== $weight($b)) {
            return $weight($a) > $weight($b) ? $a : $b;
        }

        // При равных ролях канонический — тот, что появился раньше: на нём история.
        return $a->created_at <= $b->created_at ? $a : $b;
    }

    /**
     * Переносит всё с $from на $into и помечает $from слитым.
     *
     * @return array<string, int> сколько строк перенесено по таблицам
     */
    public function merge(User $from, User $into, bool $dryRun = false): array
    {
        if ($from->id === $into->id) {
            return [];
        }

        $moved = [];

        DB::transaction(function () use ($from, $into, $dryRun, &$moved) {
            foreach (self::OWNERSHIP as $table => $columns) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                foreach ($columns as $column) {
                    if (!Schema::hasColumn($table, $column)) {
                        continue;
                    }

                    $this->dropConflicts($table, $column, $from->id, $into->id, $dryRun);

                    $query = DB::table($table)->where($column, $from->id);
                    $count = $query->count();
                    if ($count === 0) {
                        continue;
                    }

                    $moved["{$table}.{$column}"] = $count;
                    if (!$dryRun) {
                        $query->update([$column => $into->id]);
                    }
                }
            }

            if ($dryRun) {
                return;
            }

            $this->carryOverIdentity($from, $into);

            // Донора не удаляем: внешние ссылки и аудит должны остаться валидными.
            // Он просто перестаёт быть входной точкой.
            $from->update([
                'oauth_provider'    => null,
                'oauth_id'          => null,
                'telegram_chat_id'  => null,
                'telegram_oidc_sub' => null,
                'email'             => null,
                'merged_into_id'    => $into->id,
                'merged_at'         => now(),
            ]);
        });

        Log::info('accounts_merged', [
            'from' => $from->id,
            'into' => $into->id,
            'dry_run' => $dryRun,
            'moved' => $moved,
        ]);

        return $moved;
    }

    /**
     * Уникальные пары вроде (teacher_id, student_id) не переживут переноса, если
     * такая связь уже есть у канонического — лишнюю строку доноров убираем.
     */
    private function dropConflicts(string $table, string $column, int $fromId, int $intoId, bool $dryRun): void
    {
        if (!isset(self::UNIQUE_PAIRS[$table])) {
            return;
        }

        [$first, $second] = self::UNIQUE_PAIRS[$table];
        $other = $column === $first ? $second : ($column === $second ? $first : null);
        if ($other === null || !Schema::hasColumn($table, $other)) {
            return;
        }

        $existing = DB::table($table)->where($column, $intoId)->pluck($other)->all();
        if ($existing === []) {
            return;
        }

        $conflicting = DB::table($table)->where($column, $fromId)->whereIn($other, $existing);
        if (!$dryRun) {
            $conflicting->delete();
        }
    }

    /**
     * Телеграм-ключи и профильные поля донора переезжают на канонический аккаунт,
     * иначе после слияния вход через мини-апп снова создаст дубль.
     */
    private function carryOverIdentity(User $from, User $into): void
    {
        $updates = [];

        if ($into->telegram_chat_id === null && $from->telegram_chat_id !== null) {
            $updates['telegram_chat_id'] = $from->telegram_chat_id;
            $updates['telegram_linked_at'] = $from->telegram_linked_at ?? now();
        }

        if ($into->telegram_oidc_sub === null && $from->telegram_oidc_sub !== null) {
            $updates['telegram_oidc_sub'] = $from->telegram_oidc_sub;
        }

        foreach (['tg_username', 'avatar', 'first_name', 'last_name', 'grade_num', 'grade_letter',
                  'school_number', 'city', 'evrium_teacher_id', 'referred_by_user_id'] as $field) {
            if (($into->{$field} ?? null) === null && ($from->{$field} ?? null) !== null) {
                $updates[$field] = $from->{$field};
            }
        }

        if ($into->onboarding_completed_at === null && $from->onboarding_completed_at !== null) {
            $updates['onboarding_completed_at'] = $from->onboarding_completed_at;
        }

        if ($updates !== []) {
            $into->update($updates);
        }
    }
}
