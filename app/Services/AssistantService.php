<?php

namespace App\Services;

use App\Models\LessonAssistantMessage;
use App\Models\LessonSession;
use App\Models\StudentNote;
use App\Models\User;
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
     * Оркестрация реплики учителя: анонимизирует сообщение, зовёт DeepSeek
     * с набором инструментов и исполняет tool_calls.
     *
     * - record_observation  → StudentNote(kind, topic_tag, source=chat) для ученика
     * - add_lesson_note     → append к lesson_sessions.note (не затирая)
     * - answer_about_student→ подгружает записи ученика и делает ВТОРОЙ вызов chat
     *
     * Обе реплики (учитель + ассистент) пишутся в lesson_assistant_messages.
     * При падении API — fallback: реплики сохраняются, notes пуст, reply — извинение.
     * (Бесхозный student_note создать нельзя: student_id NOT NULL, некого привязать.)
     *
     * @return array{reply:string,notes:array<int,StudentNote>}
     */
    public function handleMessage(LessonSession $session, User $teacher, string $message): array
    {
        $participants = $this->sessionParticipants($session);
        [$clean, $map] = $this->anonymize($message, $participants);

        $notes = [];

        try {
            $res = $this->chat(
                [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $clean],
                ],
                $this->toolDefinitions(),
            );

            $reply = null;

            foreach ($res['tool_calls'] as $call) {
                switch ($call['name']) {
                    case 'record_observation':
                        if ($note = $this->applyRecordObservation($call['arguments'], $map, $session, $teacher)) {
                            $notes[] = $note;
                        }
                        break;

                    case 'add_lesson_note':
                        $this->applyAddLessonNote($call['arguments'], $session);
                        break;

                    case 'answer_about_student':
                        $reply = $this->answerAboutStudent(
                            $call, $map, $participants, $teacher, $clean
                        );
                        break;
                }
            }

            // Нет follow-up от answer_about_student → берём контент первого ответа.
            $reply ??= (string) ($res['content'] ?? '');
            $reply = $this->deanonymize($reply, $this->nameMap($map, $participants));

            if (trim($reply) === '') {
                // Инструмент сработал, но модель не дала текста — короткое подтверждение.
                $reply = $notes !== [] ? 'Записал.' : 'Готово.';
            }
        } catch (RuntimeException) {
            $reply = 'Не смог разобрать (ассистент недоступен), попробуйте ещё раз или запишите вручную';
            $notes = [];
        }

        $this->saveMessages($session, $message, $reply);

        return ['reply' => $reply, 'notes' => $notes];
    }

    /**
     * Участники сессии в формате для anonymize/nameMap (только с непустым именем).
     *
     * @return array<int,array{id:int,name:string}>
     */
    private function sessionParticipants(LessonSession $session): array
    {
        return $session->participants()->with('student')->get()
            ->map(fn ($p) => ['id' => $p->student_id, 'name' => (string) ($p->student?->name ?? '')])
            ->filter(fn ($p) => trim($p['name']) !== '')
            ->values()
            ->all();
    }

    /** Создаёт StudentNote по tool-call record_observation (или null, если ученик не распознан). */
    private function applyRecordObservation(array $args, array $map, LessonSession $session, User $teacher): ?StudentNote
    {
        $ref = $args['participant_ref'] ?? null;
        $studentId = $ref !== null ? ($map[$ref] ?? null) : null;
        if ($studentId === null) {
            return null; // placeholder вне сессии — пропускаем
        }

        $kind = $args['kind'] ?? 'general';
        if (! in_array($kind, ['weakness', 'strength', 'todo', 'general'], true)) {
            $kind = 'general';
        }

        return StudentNote::create([
            'student_id'        => $studentId,
            'teacher_id'        => $teacher->id,
            'lesson_session_id' => $session->id,
            'topic_tag'         => $args['topic_tag'] ?? null,
            'kind'              => $kind,
            'source'            => 'chat',
            'body'              => (string) ($args['body'] ?? ''),
        ]);
    }

    /** Дописывает общую заметку об уроке в lesson_sessions.note (append). */
    private function applyAddLessonNote(array $args, LessonSession $session): void
    {
        $body = trim((string) ($args['body'] ?? ''));
        if ($body === '') {
            return;
        }
        $existing = trim((string) ($session->note ?? ''));
        $session->update(['note' => $existing === '' ? $body : $existing . "\n" . $body]);
    }

    /**
     * Отвечает на вопрос об ученике: подгружает его записи, кладёт их результатом
     * tool-call и делает второй вызов chat за финальным текстом.
     *
     * @param  array{id:?string,name:string,arguments:array<string,mixed>}  $call
     * @param  array<string,int>  $map
     * @param  array<int,array{id:int,name:string}>  $participants
     */
    private function answerAboutStudent(array $call, array $map, array $participants, User $teacher, string $userText): string
    {
        $ref = $call['arguments']['participant_ref'] ?? null;
        $studentId = $ref !== null ? ($map[$ref] ?? null) : null;

        $recordsText = 'Записей об ученике нет.';
        if ($studentId !== null) {
            $records = StudentNote::where('student_id', $studentId)
                ->where('teacher_id', $teacher->id)
                ->latest('created_at')
                ->limit(50)
                ->get();

            if ($records->isNotEmpty()) {
                $lines = $records->map(function (StudentNote $n) use ($participants, $ref) {
                    // Тело записи тоже анонимизируем — вдруг там имена участников.
                    [$body] = $this->anonymize((string) $n->body, $participants);
                    $tag = $n->topic_tag ? " [{$n->topic_tag}]" : '';
                    return "- {$ref} ({$n->kind}){$tag}: {$body}";
                })->all();
                $recordsText = implode("\n", $lines);
            }
        }

        $callId = $call['id'] ?? 'call_1';

        $second = $this->chat([
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $userText],
            [
                'role' => 'assistant',
                'content' => null,
                'tool_calls' => [[
                    'id' => $callId,
                    'type' => 'function',
                    'function' => [
                        'name' => 'answer_about_student',
                        'arguments' => json_encode($call['arguments'], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
            ],
            [
                'role' => 'tool',
                'tool_call_id' => $callId,
                'content' => $recordsText,
            ],
        ], []);

        return (string) ($second['content'] ?? '');
    }

    /** Пишет обе реплики (учитель + ассистент) в историю сессии. */
    private function saveMessages(LessonSession $session, string $teacherText, string $assistantText): void
    {
        LessonAssistantMessage::create([
            'lesson_session_id' => $session->id,
            'role'              => 'teacher',
            'content'           => $teacherText,
        ]);
        LessonAssistantMessage::create([
            'lesson_session_id' => $session->id,
            'role'              => 'assistant',
            'content'           => $assistantText,
        ]);
    }

    /** Системный промпт (RU) для ассистента учителя математики. */
    private function systemPrompt(): string
    {
        return 'Ты помощник учителя математики. Учитель фиксирует наблюдения об учениках '
            . '(обозначены P1, P2 и т.д.). Вызывай record_observation для наблюдения о конкретном '
            . 'ученике, add_lesson_note для общей заметки об уроке, answer_about_student чтобы '
            . 'ответить на вопрос об ученике. kind: weakness (западает) / strength (силён) / '
            . 'todo (надо сделать) / general (прочее). topic_tag — тема или навык ОГЭ. Отвечай кратко по-русски.';
    }

    /**
     * Определения инструментов в OpenAI-формате function calling.
     *
     * @return array<int,array<string,mixed>>
     */
    private function toolDefinitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'record_observation',
                    'description' => 'Зафиксировать наблюдение о конкретном ученике.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'participant_ref' => ['type' => 'string', 'description' => 'Плейсхолдер ученика: P1, P2, ...'],
                            'kind' => ['type' => 'string', 'enum' => ['weakness', 'strength', 'todo', 'general']],
                            'topic_tag' => ['type' => 'string', 'description' => 'Тема или навык ОГЭ'],
                            'body' => ['type' => 'string', 'description' => 'Суть наблюдения'],
                        ],
                        'required' => ['participant_ref', 'kind', 'body'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'add_lesson_note',
                    'description' => 'Добавить общую заметку об уроке (не про конкретного ученика).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'body' => ['type' => 'string', 'description' => 'Текст заметки'],
                        ],
                        'required' => ['body'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'answer_about_student',
                    'description' => 'Ответить на вопрос учителя об ученике по накопленным записям.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'participant_ref' => ['type' => 'string', 'description' => 'Плейсхолдер ученика: P1, P2, ...'],
                        ],
                        'required' => ['participant_ref'],
                    ],
                ],
            ],
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
