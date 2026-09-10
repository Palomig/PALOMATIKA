<?php

namespace App\Console\Commands;

use App\Services\EgeTaskDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Перенумерация профильного банка ЕГЭ с плана КИМ 2026 на план 2027.
 *
 * Почему не переимпорт банка: он пересобирает тему целиком и стирает ручные
 * переносы заданий (`tasks:move-groups`) вместе с разметкой подтипов. Здесь
 * меняется РОВНО одно поле — `topic` у тем и групп банка `ege`.
 *
 * Что произошло в плане 2027 (проект спецификации ФИПИ от 21.08.2026):
 *   • появилось задание 6  — случайная величина, мат. ожидание, дисперсия;
 *   • появилось задание 17 — модель из другого учебного предмета;
 *   • отдельного «наибольшего и наименьшего» (было 12) больше нет: оно
 *     слилось с графиком производной (было 8) в новое задание 9;
 *   • экономическая (была 16) уехала из части 2 в часть 1 и стала 13.
 * Из-за этого номера начиная с шестого едут сквозняком.
 *
 * Переименование идёт В ДВА ХОДА через временный префикс: номера сдвигаются
 * вверх, и прямое переименование затирало бы ещё не переехавшего соседа
 * (17 → 18 поверх живого 18). У двух номеров, которые сливаются в девятый,
 * позиции групп разводятся сдвигом, иначе порядок внутри задания перемешался
 * бы: у банка позиции и так не уникальны — под одной темой лежат и задачи
 * ФИПИ, и скрытый старый банк Паломатики.
 */
class RenumberEgeProf2027 extends Command
{
    protected $signature = 'ege:renumber-2027 {--dry-run : показать план, ничего не менять}';

    protected $description = 'Профиль ЕГЭ: номера заданий с плана КИМ 2026 на план 2027';

    /** Банк профиля. База (`ege_b`) не трогается: её структура не менялась. */
    private const BANK = EgeTaskDataService::BANK_PROF;

    /** Старый номер → новый. Восьмой и двенадцатый сходятся в девятом. */
    private const MAP = [
        '01' => '01', '02' => '02', '03' => '03', '04' => '04', '05' => '05',
        '06' => '07', '07' => '08', '08' => '09', '09' => '10', '10' => '11',
        '11' => '12',
        '12' => '09',
        '13' => '14', '14' => '15', '15' => '16',
        '16' => '13',
        '17' => '18', '18' => '19', '19' => '20',
    ];

    /** Номер, в который вливается прежний двенадцатый. */
    private const MERGED_INTO = '09';
    private const MERGED_FROM = '12';

    private const TMP_PREFIX = 'n';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $before = $this->countsByTopic();
        if ($before === []) {
            $this->error('В банке ' . self::BANK . ' нет ни одной группы заданий.');
            return self::FAILURE;
        }

        if (array_key_exists('20', $before)) {
            $this->info('Банк уже на плане КИМ 2027 (есть задание 20) — делать нечего.');
            return self::SUCCESS;
        }

        $unknown = array_diff(array_keys($before), array_keys(self::MAP));
        if ($unknown !== []) {
            $this->error('Неизвестные номера заданий: ' . implode(', ', $unknown)
                . '. Карта перехода их не описывает, перенумерация отменена.');
            return self::FAILURE;
        }

        $this->table(
            ['было', 'станет', 'групп'],
            collect($before)
                ->map(fn (int $count, string $topic) => [$topic, self::MAP[$topic], $count])
                ->sortBy(0)
                ->values()
                ->all()
        );

        if ($dry) {
            $this->info('Сухой прогон: ничего не изменено.');
            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            // Прежний двенадцатый встаёт В КОНЕЦ девятого, а не вперемешку.
            $offset = (int) DB::table('task_groups')
                ->where('bank', self::BANK)
                ->where('topic', '08')          // будущий девятый
                ->max('position');
            DB::table('task_groups')
                ->where('bank', self::BANK)
                ->where('topic', self::MERGED_FROM)
                ->update(['position' => DB::raw('position + ' . ($offset + 1))]);

            // Ход 1: во временные имена, чтобы сдвиг вверх никого не затёр.
            foreach (self::MAP as $old => $new) {
                // Ключи карты — строки «01»…«19», но «10» и дальше PHP хранит
                // целыми. Целое в WHERE заставляет MySQL сравнивать колонку
                // ЧИСЛЕННО, и на временном имени «n01» запрос падает.
                $old = str_pad((string) $old, 2, '0', STR_PAD_LEFT);
                foreach (['task_groups', 'task_topics'] as $table) {
                    DB::table($table)
                        ->where('bank', self::BANK)
                        ->where('topic', $old)
                        ->update(['topic' => self::TMP_PREFIX . $new]);
                }
            }

            // Тема-описание у слитых номеров одна: девятому достаётся карточка
            // производной (прежний восьмой), лишнюю строку убираем.
            $topicRows = DB::table('task_topics')
                ->where('bank', self::BANK)
                ->where('topic', self::TMP_PREFIX . self::MERGED_INTO)
                ->orderBy('id')
                ->pluck('id');
            if ($topicRows->count() > 1) {
                DB::table('task_topics')->whereIn('id', $topicRows->slice(1))->delete();
            }

            // Ход 2: временные имена — в настоящие.
            foreach (array_unique(array_values(self::MAP)) as $new) {
                foreach (['task_groups', 'task_topics'] as $table) {
                    DB::table($table)
                        ->where('bank', self::BANK)
                        ->where('topic', self::TMP_PREFIX . $new)
                        ->update(['topic' => $new]);
                }
            }

            // Подписи заданий лежат в payload темы и после переезда врут:
            // под номером 13 стояло бы «Уравнение (часть 2)».
            $meta = (new EgeTaskDataService())->getAllTopicsMeta();
            foreach (DB::table('task_topics')->where('bank', self::BANK)->get() as $row) {
                $payload = json_decode((string) $row->payload, true) ?: [];
                $topic = (string) $row->topic;
                if (!isset($meta[$topic])) {
                    continue;
                }
                $payload['topic_id'] = $topic;
                $payload['meta'] = array_merge($payload['meta'] ?? [], [
                    'title' => $meta[$topic]['title'],
                    'description' => $meta[$topic]['description'],
                    'color' => $meta[$topic]['color'],
                    'icon' => $meta[$topic]['icon'],
                ]);
                DB::table('task_topics')->where('id', $row->id)
                    ->update(['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
            }
        });

        // Темы кешируются на час по ключу «банк + номер»: без сброса витрина
        // ещё час показывала бы старую раскладку.
        (new EgeTaskDataService())->clearCache();

        $after = $this->countsByTopic();
        $this->table(
            ['номер', 'групп'],
            collect($after)->map(fn (int $c, string $t) => [$t, $c])->sortBy(0)->values()->all()
        );
        $this->info('Групп до: ' . array_sum($before) . ', после: ' . array_sum($after));

        return array_sum($before) === array_sum($after) ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<string, int> */
    private function countsByTopic(): array
    {
        return DB::table('task_groups')
            ->where('bank', self::BANK)
            ->selectRaw('topic, COUNT(*) c')
            ->groupBy('topic')
            ->pluck('c', 'topic')
            ->map(fn ($c) => (int) $c)
            ->all();
    }
}
