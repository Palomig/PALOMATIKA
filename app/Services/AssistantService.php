<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Клиент DeepSeek (OpenAI-совместимый Chat Completions с function calling)
 * плюс анонимизация имён участников перед отправкой в LLM.
 *
 * Приватность: имена учеников НИКОГДА не уходят в API. Перед вызовом
 * `chat()` текст прогоняется через `anonymize()` (имя → плейсхолдер Pn),
 * а ответ модели разворачивается обратно через `deanonymize()`.
 *
 * Провайдер задаётся через config('services.deepseek.*') и легко
 * свапается на совместимый (Kimi и т.п.) сменой env.
 */
class AssistantService
{
    /**
     * Заменяет имена участников на плейсхолдеры P1..Pn.
     *
     * @param  array<int,array{id:int|string,name:string}>  $participants
     * @return array{0:string,1:array<string,int>}  [$cleanText, $map] где $map = ['P1' => student_id, ...]
     */
    public function anonymize(string $text, array $participants): array
    {
        // Стабильный порядок плейсхолдеров: по исходному индексу участника.
        $entries = [];
        $i = 0;
        foreach ($participants as $p) {
            $i++;
            $name = trim((string) ($p['name'] ?? ''));
            $id = $p['id'] ?? null;
            if (mb_strlen($name) < 2 || $id === null) {
                continue; // пустое имя / один символ — пропускаем
            }
            $entries[] = [
                'placeholder' => 'P' . $i,
                'name' => $name,
                'id' => (int) $id,
            ];
        }

        // Заменяем более длинные имена первыми, чтобы «Аня Ковалёва»
        // заменилось раньше, чем «Аня» (иначе останется висячее «Ковалёва»).
        $ordered = $entries;
        usort($ordered, fn ($a, $b) => mb_strlen($b['name']) <=> mb_strlen($a['name']));

        $clean = $text;
        $map = [];
        foreach ($ordered as $e) {
            // preg с флагами iu — регистронезависимо и для кириллицы
            // (str_ireplace фолдит только ASCII, «Аня» не совпало бы с «аня»).
            $pattern = '/' . preg_quote($e['name'], '/') . '/iu';
            $clean = preg_replace($pattern, $e['placeholder'], $clean) ?? $clean;
        }

        // map строим в исходном порядке (P1, P2, ...) для читаемости.
        foreach ($entries as $e) {
            $map[$e['placeholder']] = $e['id'];
        }

        return [$clean, $map];
    }

    /**
     * Строит карту плейсхолдер → имя для показа учителю.
     * Разворачивает `anonymize()`-карту (placeholder → student_id)
     * обратно в имена по списку участников.
     *
     * @param  array<string,int>  $map  ['P1' => student_id, ...]
     * @param  array<int,array{id:int|string,name:string}>  $participants
     * @return array<string,string>  ['P1' => 'Петя', ...]
     */
    public function nameMap(array $map, array $participants): array
    {
        $namesById = [];
        foreach ($participants as $p) {
            if (isset($p['id'])) {
                $namesById[(int) $p['id']] = (string) ($p['name'] ?? '');
            }
        }

        $out = [];
        foreach ($map as $placeholder => $id) {
            $out[$placeholder] = $namesById[(int) $id] ?? ('#' . $id);
        }

        return $out;
    }

    /**
     * Разворачивает плейсхолдеры обратно в реальные значения (для показа учителю).
     *
     * @param  array<string,string>  $map  placeholder → имя (см. nameMap)
     */
    public function deanonymize(string $text, array $map): string
    {
        // Более длинные плейсхолдеры первыми (P10 раньше P1),
        // иначе «P1» съест префикс «P10».
        $placeholders = array_keys($map);
        usort($placeholders, fn ($a, $b) => mb_strlen((string) $b) <=> mb_strlen((string) $a));

        $result = $text;
        foreach ($placeholders as $placeholder) {
            $result = str_replace((string) $placeholder, (string) $map[$placeholder], $result);
        }

        return $result;
    }

    /**
     * Вызывает DeepSeek Chat Completions.
     *
     * @param  array<int,array<string,mixed>>  $messages
     * @param  array<int,array<string,mixed>>  $tools
     * @return array{content:?string,tool_calls:array<int,array{id:?string,name:string,arguments:array<string,mixed>}>}
     *
     * @throws RuntimeException при HTTP-ошибке (в вызывающем коде — try/catch + fallback)
     */
    public function chat(array $messages, array $tools = []): array
    {
        $response = Http::withToken(config('services.deepseek.api_key'))
            ->baseUrl(rtrim((string) config('services.deepseek.base_url'), '/'))
            ->timeout(15)
            ->retry(1, 200, throw: false)
            ->post('/chat/completions', [
                'model' => config('services.deepseek.model'),
                'messages' => $messages,
                'tools' => $tools ?: null,
                'tool_choice' => $tools ? 'auto' : null,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'DeepSeek chat completion failed: HTTP ' . $response->status()
            );
        }

        $message = $response->json('choices.0.message') ?? [];

        return [
            'content' => $message['content'] ?? null,
            'tool_calls' => $this->parseToolCalls($message['tool_calls'] ?? []),
        ];
    }

    /**
     * Нормализует tool_calls: arguments из JSON-строки → массив.
     *
     * @param  array<int,array<string,mixed>>  $rawToolCalls
     * @return array<int,array{id:?string,name:string,arguments:array<string,mixed>}>
     */
    private function parseToolCalls(array $rawToolCalls): array
    {
        $calls = [];
        foreach ($rawToolCalls as $call) {
            $fn = $call['function'] ?? [];
            $name = $fn['name'] ?? null;
            if ($name === null) {
                continue;
            }

            $arguments = $fn['arguments'] ?? [];
            if (is_string($arguments)) {
                $decoded = json_decode($arguments, true);
                $arguments = is_array($decoded) ? $decoded : [];
            }

            $calls[] = [
                'id' => $call['id'] ?? null,
                'name' => $name,
                'arguments' => $arguments,
            ];
        }

        return $calls;
    }
}
