<?php

namespace Tests\Feature;

use App\Models\LessonAssistantMessage;
use App\Models\LessonSession;
use App\Models\StudentNote;
use App\Models\User;
use App\Services\AssistantService;
use App\Services\LessonSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Оркестрация чата ассистента: handleMessage исполняет tool_calls DeepSeek
 * (record_observation / add_lesson_note / answer_about_student), пишет
 * student_notes и историю сообщений. Реальный API не дёргается — Http::fake.
 */
class AssistantChatTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::create([
            'name' => 'Mrs. Ivanova', 'email' => 't+' . uniqid() . '@t.t',
            'password' => 'x', 'role' => 'teacher',
        ]);
    }

    private function student(string $name): User
    {
        return User::create([
            'name' => $name, 'email' => 's+' . uniqid() . '@t.t',
            'password' => 'x', 'role' => 'student', 'onboarding_completed_at' => now(),
        ]);
    }

    /** Создаёт adhoc-сессию с участником и возвращает [session, teacher, student]. */
    private function sessionWith(User $teacher, User $student): LessonSession
    {
        $svc = app(LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        $svc->joinByCode($session->join_code, $student);

        return $session->fresh();
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.deepseek.api_key', 'test-key');
        config()->set('services.deepseek.base_url', 'https://api.deepseek.com');
        config()->set('services.deepseek.model', 'deepseek-chat');
    }

    public function test_record_observation_creates_note_and_saves_messages(): void
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

        $teacher = $this->teacher();
        $petya = $this->student('Петя');
        $session = $this->sessionWith($teacher, $petya);

        $result = app(AssistantService::class)
            ->handleMessage($session, $teacher, 'Петя путает подобие');

        // Запись создана для нужного ученика с тегами
        $this->assertDatabaseHas('student_notes', [
            'student_id'        => $petya->id,
            'teacher_id'        => $teacher->id,
            'lesson_session_id' => $session->id,
            'kind'              => 'weakness',
            'topic_tag'         => 'геометрия: подобие',
            'source'            => 'chat',
        ]);

        // Возврат: одна созданная запись + непустой ответ
        $this->assertCount(1, $result['notes']);
        $this->assertNotEmpty($result['reply']);

        // Обе реплики сохранены в историю
        $this->assertDatabaseHas('lesson_assistant_messages', [
            'lesson_session_id' => $session->id,
            'role'              => 'teacher',
            'content'           => 'Петя путает подобие',
        ]);
        $this->assertSame(2, LessonAssistantMessage::where('lesson_session_id', $session->id)->count());

        // Имя ученика анонимизировано — в API не ушло
        Http::assertSent(fn ($r) => ! str_contains($r->body(), 'Петя'));
    }

    public function test_add_lesson_note_appends_to_session_note(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'c1',
                            'type' => 'function',
                            'function' => [
                                'name' => 'add_lesson_note',
                                'arguments' => json_encode(['body' => 'прошли теорему Пифагора']),
                            ],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $teacher = $this->teacher();
        $petya = $this->student('Петя');
        $session = $this->sessionWith($teacher, $petya);
        $session->update(['note' => 'старая заметка']);

        app(AssistantService::class)
            ->handleMessage($session, $teacher, 'Запиши: прошли теорему Пифагора');

        $note = $session->fresh()->note;
        $this->assertStringContainsString('старая заметка', $note); // не затёрли
        $this->assertStringContainsString('прошли теорему Пифагора', $note);
    }

    public function test_answer_about_student_does_second_call_with_records(): void
    {
        // Заранее — записи ученика
        $teacher = $this->teacher();
        $petya = $this->student('Петя');
        $session = $this->sessionWith($teacher, $petya);

        StudentNote::create([
            'student_id' => $petya->id, 'teacher_id' => $teacher->id,
            'kind' => 'weakness', 'topic_tag' => 'геометрия: подобие',
            'source' => 'chat', 'body' => 'путает признаки подобия',
        ]);
        StudentNote::create([
            'student_id' => $petya->id, 'teacher_id' => $teacher->id,
            'kind' => 'strength', 'topic_tag' => 'дроби',
            'source' => 'chat', 'body' => 'хорошо считает дроби',
        ]);

        // Два последовательных ответа: сначала tool_call, потом финальный текст
        Http::fakeSequence('*/chat/completions')
            ->push([
                'choices' => [[
                    'message' => [
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call_1',
                            'type' => 'function',
                            'function' => [
                                'name' => 'answer_about_student',
                                'arguments' => json_encode(['participant_ref' => 'P1']),
                            ],
                        ]],
                    ],
                ]],
            ])
            ->push([
                'choices' => [[
                    'message' => ['content' => 'У P1 западает подобие, но силён в дробях'],
                ]],
            ]);

        $result = app(AssistantService::class)
            ->handleMessage($session, $teacher, 'Что у Пети западает?');

        // Финальный ответ — из второго вызова, с развёрнутым именем
        $this->assertStringContainsString('подобие', $result['reply']);
        $this->assertStringContainsString('Петя', $result['reply']);

        // Было именно два обращения к API
        Http::assertSentCount(2);
    }

    public function test_fallback_when_api_fails(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(['error' => 'boom'], 500),
        ]);

        $teacher = $this->teacher();
        $petya = $this->student('Петя');
        $session = $this->sessionWith($teacher, $petya);

        $result = app(AssistantService::class)
            ->handleMessage($session, $teacher, 'Петя путает подобие');

        // Fallback: ничего не создано, ответ про недоступность
        $this->assertSame([], $result['notes']);
        $this->assertStringContainsString('не', mb_strtolower($result['reply']));
        $this->assertSame(0, StudentNote::count());

        // Реплики всё равно сохранены
        $this->assertSame(2, LessonAssistantMessage::where('lesson_session_id', $session->id)->count());
    }
}
