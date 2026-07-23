<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use RuntimeException;

/**
 * Вступительная работа в 10 математический класс.
 *
 * Загружает статические варианты, собирает задачи по номерам, проверяет ответы и
 * упаковывает правильный ответ/решение в зашифрованный токен, чтобы клиент не
 * видел ответ до проверки или явного раскрытия.
 */
class Entrance10Service
{
    private const DATA_PATH = 'app/tasks/entrance10/variants.json';

    private ?array $data = null;

    public function __construct(private readonly Entrance10Generator $generator)
    {
    }

    // ---------------------------------------------------------------- данные

    private function data(): array
    {
        if ($this->data === null) {
            $path = storage_path(self::DATA_PATH);
            $raw = @file_get_contents($path);
            if ($raw === false) {
                throw new RuntimeException('Entrance10 dataset missing: ' . $path);
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                throw new RuntimeException('Entrance10 dataset is not valid JSON');
            }
            $this->data = $decoded;
        }
        return $this->data;
    }

    public function meta(): array
    {
        return $this->data()['meta'] ?? [];
    }

    /** @return array<int, array{number:int,title:string,icon:string,generatable:bool}> */
    public function numbers(): array
    {
        $out = [];
        foreach (($this->data()['numbers'] ?? []) as $n => $info) {
            $out[(int) $n] = [
                'number' => (int) $n,
                'title' => $info['title'] ?? ('Задание ' . $n),
                'icon' => $info['icon'] ?? '•',
                'generatable' => (bool) ($info['generatable'] ?? false),
            ];
        }
        ksort($out);
        return $out;
    }

    public function numberInfo(int $number): array
    {
        $info = $this->numbers()[$number] ?? null;
        if ($info === null) {
            throw new InvalidArgumentException("Unknown entrance10 number [{$number}]");
        }
        return $info;
    }

    /** @return array<int, int> список номеров вариантов */
    public function variantNumbers(): array
    {
        return array_map(static fn (array $v): int => (int) $v['number'], $this->data()['variants'] ?? []);
    }

    public function rawVariant(int $number): array
    {
        foreach (($this->data()['variants'] ?? []) as $variant) {
            if ((int) $variant['number'] === $number) {
                return $variant;
            }
        }
        throw new InvalidArgumentException("Unknown entrance10 variant [{$number}]");
    }

    /**
     * Вариант целиком, подготовленный для показа: каждая часть с токеном вместо ответа.
     */
    public function variantForView(int $number): array
    {
        $variant = $this->rawVariant($number);
        $variant['tasks'] = array_map(
            fn (array $task) => $this->prepareTask($task, "v{$number}"),
            $variant['tasks']
        );
        return $variant;
    }

    /**
     * Все статические задачи одного номера из всех вариантов (подготовленные для показа).
     */
    public function staticTasksForNumber(int $number): array
    {
        $out = [];
        foreach (($this->data()['variants'] ?? []) as $variant) {
            foreach (($variant['tasks'] ?? []) as $task) {
                if ((int) $task['number'] === $number) {
                    $task['source'] = 'Вариант ' . $variant['number'];
                    $out[] = $this->prepareTask($task, 'v' . $variant['number']);
                }
            }
        }
        return $out;
    }

    /**
     * Сгенерированная задача номера, подготовленная для показа.
     */
    public function generatedTaskForView(int $number): array
    {
        $task = $this->generator->generate($number);
        $task['source'] = 'Сгенерировано';
        return $this->prepareTask($task, 'gen');
    }

    public function isGeneratable(int $number): bool
    {
        return $this->generator->isGeneratable($number);
    }

    // ---------------------------------------------------------------- токены

    /**
     * Готовит задачу к показу: из каждой части убирает answer/solution/answer_display,
     * заменяя их на зашифрованный токен.
     */
    private function prepareTask(array $task, string $scope): array
    {
        $parts = array_map(function (array $p) use ($scope): array {
            $token = $this->encodeToken([
                'check' => $p['check'] ?? 'display',
                'answer' => (string) ($p['answer'] ?? ''),
                'answer_display' => (string) ($p['answer_display'] ?? ''),
                'solution' => (string) ($p['solution'] ?? ''),
            ]);
            return [
                'label' => $p['label'] ?? null,
                'points' => (int) ($p['points'] ?? 0),
                'text' => (string) ($p['text'] ?? ''),
                'check' => (string) ($p['check'] ?? 'display'),
                'token' => $token,
            ];
        }, $task['parts'] ?? []);

        return [
            'number' => (int) $task['number'],
            'title' => (string) ($task['title'] ?? ''),
            'source' => $task['source'] ?? null,
            'generated' => (bool) ($task['generated'] ?? false),
            'parts' => $parts,
        ];
    }

    public function encodeToken(array $payload): string
    {
        return Crypt::encryptString(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function decodeToken(string $token): ?array
    {
        try {
            $decoded = json_decode(Crypt::decryptString($token), true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            return null;
        }
    }

    // ---------------------------------------------------------------- проверка

    /**
     * Проверяет пользовательский ответ по токену.
     *
     * @return array{status:string, correct?:bool, answer_display?:string, solution?:string}
     */
    public function check(string $token, string $userAnswer, bool $reveal = false): array
    {
        $payload = $this->decodeToken($token);
        if ($payload === null) {
            return ['status' => 'bad_token'];
        }

        $check = (string) ($payload['check'] ?? 'display');
        $answerDisplay = (string) ($payload['answer_display'] ?? '');
        $solution = (string) ($payload['solution'] ?? '');

        if ($reveal || $check === 'display') {
            return [
                'status' => 'revealed',
                'answer_display' => $answerDisplay,
                'solution' => $solution,
            ];
        }

        $correct = $this->isCorrect($check, (string) ($payload['answer'] ?? ''), $userAnswer);

        $result = ['status' => 'checked', 'correct' => $correct];
        if ($correct) {
            $result['answer_display'] = $answerDisplay;
            $result['solution'] = $solution;
        }
        return $result;
    }

    public function isCorrect(string $check, string $canonical, string $user): bool
    {
        return match ($check) {
            'number', 'number_set' => $this->checkNumberSet($canonical, $user),
            'param_condition' => $this->checkParamCondition($canonical, $user),
            'yesno' => $this->checkYesNo($canonical, $user),
            default => false,
        };
    }

    private function checkNumberSet(string $canonical, string $user): bool
    {
        $expected = $this->parseNumericSet($canonical);
        $got = $this->parseNumericSet($user);
        if ($expected === null || $got === null) {
            // запасной вариант — строгое строковое сравнение
            return $this->normalizeString($canonical) === $this->normalizeString($user) && $this->normalizeString($user) !== '';
        }
        if (count($expected) !== count($got)) {
            return false;
        }
        foreach ($expected as $i => $v) {
            if (abs($v - $got[$i]) > 1e-6) {
                return false;
            }
        }
        return true;
    }

    /** @return array<int, float>|null отсортированный список значений или null при неудаче */
    private function parseNumericSet(string $s): ?array
    {
        $s = trim($s);
        if ($s === '') {
            return null;
        }
        // разделители: ; , пробелы, «и»
        $s = str_ireplace([' и ', ' и', 'и '], ';', $s);
        $parts = preg_split('/[;,\s]+/u', $s, -1, PREG_SPLIT_NO_EMPTY);
        $values = [];
        foreach ($parts as $token) {
            $v = $this->parseNumericToken($token);
            if ($v === null) {
                return null;
            }
            $values[] = $v;
        }
        if (empty($values)) {
            return null;
        }
        sort($values);
        return $values;
    }

    private function parseNumericToken(string $token): ?float
    {
        $t = mb_strtolower(trim($token));
        $t = str_replace(['−', '–', '—'], '-', $t);
        $t = str_replace(',', '.', $t);
        $t = str_replace(' ', '', $t);
        // sqrt(6) | sqrt6 | корень6 -> √6
        $t = preg_replace('/sqrt\(([0-9.]+)\)/u', '√$1', $t) ?? $t;
        $t = str_replace(['sqrt', 'корень', 'root', '\\sqrt'], '√', $t);
        $t = str_replace(['(', ')', '{', '}'], '', $t);
        if ($t === '' || $t === '-') {
            return null;
        }

        // ±coef√rad  (coef может отсутствовать => 1)
        if (preg_match('/^(-?)(\d+(?:\.\d+)?)?\*?√(\d+(?:\.\d+)?)$/u', $t, $m)) {
            $sign = $m[1] === '-' ? -1.0 : 1.0;
            $coef = ($m[2] === '' || $m[2] === null) ? 1.0 : (float) $m[2];
            $rad = (float) $m[3];
            return $sign * $coef * sqrt($rad);
        }
        // дробь a/b
        if (preg_match('/^(-?\d+(?:\.\d+)?)\/(\d+(?:\.\d+)?)$/u', $t, $m)) {
            $den = (float) $m[2];
            if (abs($den) < 1e-12) {
                return null;
            }
            return (float) $m[1] / $den;
        }
        // обычное число
        if (preg_match('/^-?\d+(?:\.\d+)?$/u', $t)) {
            return (float) $t;
        }
        return null;
    }

    private function checkParamCondition(string $canonical, string $user): bool
    {
        $expected = $this->conditionAtoms($canonical);
        $got = $this->conditionAtoms($user);
        return $expected !== [] && $expected === $got;
    }

    /** @return array<int, string> отсортированные атомы вида «≠1», «>0» */
    private function conditionAtoms(string $s): array
    {
        $s = mb_strtolower($s);
        $s = str_replace(['−', '–', '—'], '-', $s);
        $s = str_replace(',', '.', $s);
        $s = str_replace(['≠', '!=', '<>', '\\ne', '\\neq'], '≠', $s);
        $s = str_replace(['≥', '>=', '\\ge', '\\geq'], '≥', $s);
        $s = str_replace(['≤', '<=', '\\le', '\\leq'], '≤', $s);
        $s = str_replace(' ', '', $s);

        preg_match_all('/(≠|≥|≤|>|<|=)(-?\d+(?:\.\d+)?)/u', $s, $matches, PREG_SET_ORDER);
        $atoms = [];
        foreach ($matches as $mm) {
            $num = (float) $mm[2];
            $numStr = (abs($num - round($num)) < 1e-9) ? (string) (int) round($num) : rtrim(rtrim(sprintf('%.6F', $num), '0'), '.');
            $atoms[] = $mm[1] . $numStr;
        }
        $atoms = array_values(array_unique($atoms));
        sort($atoms);
        return $atoms;
    }

    private function checkYesNo(string $canonical, string $user): bool
    {
        return $this->yesNoValue($canonical) === $this->yesNoValue($user) && $this->yesNoValue($user) !== '';
    }

    private function yesNoValue(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[^a-zа-яё+\-]/u', '', $s) ?? $s;
        if (in_array($s, ['да', 'yes', 'y', '+', 'можно', 'верно', 'true'], true)) {
            return 'да';
        }
        if (in_array($s, ['нет', 'no', 'n', '-', 'нельзя', 'неверно', 'false'], true)) {
            return 'нет';
        }
        return '';
    }

    private function normalizeString(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace(['−', '–', '—', ',', ' '], ['-', '-', '-', '.', ''], $s);
        return $s;
    }
}
