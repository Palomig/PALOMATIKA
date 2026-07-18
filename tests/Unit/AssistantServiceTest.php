<?php

namespace Tests\Unit;

use App\Services\AssistantService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantServiceTest extends TestCase
{
    private array $participants = [
        ['id' => 10, 'name' => 'Петя'],
        ['id' => 11, 'name' => 'Аня Ковалёва'],
    ];

    public function test_anonymize_replaces_participant_names(): void
    {
        $service = new AssistantService();

        [$clean, $map] = $service->anonymize(
            'Петя опять путает подобие, а Аня Ковалёва молодец',
            $this->participants
        );

        $this->assertStringNotContainsString('Петя', $clean);
        $this->assertStringNotContainsString('Аня', $clean);
        $this->assertStringNotContainsString('Ковалёва', $clean);
        $this->assertStringContainsString('P1', $clean);
        $this->assertStringContainsString('P2', $clean);

        // map: placeholder → student_id, оба id присутствуют
        $this->assertContains(10, $map);
        $this->assertContains(11, $map);
    }

    public function test_anonymize_is_case_insensitive_and_longest_first(): void
    {
        $service = new AssistantService();

        // «Аня Ковалёва» должна замениться раньше, чем «Аня»,
        // иначе останется висячее «Ковалёва».
        [$clean] = $service->anonymize('аня ковалёва разбирается', $this->participants);

        $this->assertStringNotContainsString('ковалёва', mb_strtolower($clean));
        $this->assertStringNotContainsString('Ковалёва', $clean);
    }

    public function test_deanonymize_restores(): void
    {
        $service = new AssistantService();

        [$clean, $map] = $service->anonymize('Петя молодец', $this->participants);

        // Плейсхолдер, соответствующий Пете
        $placeholder = array_search(10, $map, true);
        $this->assertNotFalse($placeholder);
        $this->assertStringNotContainsString('Петя', $clean);

        // Для показа учителю: placeholder → имя
        $nameMap = $service->nameMap($map, $this->participants);
        $this->assertSame('Петя', $nameMap[$placeholder]);

        $restored = $service->deanonymize($clean, $nameMap);
        $this->assertStringContainsString('Петя', $restored);
    }

    public function test_chat_sends_anonymized_and_returns_tool_calls(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'c1',
                            'type' => 'function',
                            'function' => [
                                'name' => 'record_observation',
                                'arguments' => json_encode([
                                    'participant_ref' => 'P1',
                                    'kind' => 'weakness',
                                    'topic_tag' => 'геометрия: подобие',
                                    'body' => 'путает признаки подобия',
                                ]),
                            ],
                        ]],
                    ],
                ]],
            ]),
        ]);

        config()->set('services.deepseek.api_key', 'test-key');
        config()->set('services.deepseek.base_url', 'https://api.deepseek.com');
        config()->set('services.deepseek.model', 'deepseek-chat');

        $service = new AssistantService();

        $tools = [[
            'type' => 'function',
            'function' => ['name' => 'record_observation', 'parameters' => []],
        ]];

        $result = $service->chat(
            [['role' => 'user', 'content' => 'P1 опять путает подобие']],
            $tools
        );

        // Возвращённые tool_calls: name + декодированные arguments (массив)
        $this->assertNotEmpty($result['tool_calls']);
        $call = $result['tool_calls'][0];
        $this->assertSame('record_observation', $call['name']);
        $this->assertIsArray($call['arguments']);
        $this->assertSame('P1', $call['arguments']['participant_ref']);
        $this->assertSame('weakness', $call['arguments']['kind']);

        // tools ушли в запрос
        Http::assertSent(fn ($r) => str_contains($r->body(), 'record_observation'));
        // Bearer-токен из конфига
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer test-key'));
    }

    public function test_chat_returns_empty_tool_calls_when_absent(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Просто ответ без инструментов'],
                ]],
            ]),
        ]);

        config()->set('services.deepseek.api_key', 'test-key');

        $service = new AssistantService();
        $result = $service->chat([['role' => 'user', 'content' => 'привет']], []);

        $this->assertSame('Просто ответ без инструментов', $result['content']);
        $this->assertSame([], $result['tool_calls']);
    }

    public function test_chat_throws_on_http_failure(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(['error' => 'boom'], 500),
        ]);

        config()->set('services.deepseek.api_key', 'test-key');

        $this->expectException(\RuntimeException::class);

        (new AssistantService())->chat([['role' => 'user', 'content' => 'x']], []);
    }
}
