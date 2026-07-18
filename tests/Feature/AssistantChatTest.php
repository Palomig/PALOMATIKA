<?php

namespace Tests\Feature;

use App\Models\LessonSession;
use App\Models\StudentNote;
use App\Models\User;
use App\Services\AssistantService;
use App\Services\LessonSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Форма быстрой записи: учитель ЯВНО выбирает учеников и пишет текст,
 * DeepSeek только вытаскивает теги {kind, topic_tag} через один tool tag_note.
 * Реальный API не дёргается — только Http::fake.
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

    /** Создаёт adhoc-сессию с участниками и возвращает сессию. */
    private function sessionWith(User $teacher, User ...$students): LessonSession
    {
        $svc = app(LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        foreach ($students as $student) {
            $svc->joinByCode($session->join_code, $student);
        }

        return $session->fresh();
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.deepseek.api_key', 'test-key');
        config()->set('services.deepseek.base_url', 'https://api.deepseek.com');
        config()->set('services.deepseek.model', 'deepseek-chat');
    }

    private function fakeTagNote(string $kind, ?string $topicTag): void
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
                                'name' => 'tag_note',
                                'arguments' => json_encode([
                                    'kind' => $kind,
                                    'topic_tag' => $topicTag,
                                ]),
                            ],
                        ]],
                    ],
                ]],
            ]),
        ]);
    }

    public function test_record_note_creates_note_per_student_with_tags(): void
    {
        $this->fakeTagNote('weakness', 'геометрия');

        $teacher = $this->teacher();
        $petya = $this->student('Петя');
        $vasya = $this->student('Вася');
        $session = $this->sessionWith($teacher, $petya, $vasya);

        $text = 'Петя и Вася путают признаки подобия';
        $result = app(AssistantService::class)
            ->recordNote($session, $teacher, [$petya->id, $vasya->id], $text);

        $this->assertSame('weakness', $result['kind']);
        $this->assertSame('геометрия', $result['topic_tag']);
        $this->assertCount(2, $result['notes']);

        foreach ([$petya, $vasya] as $student) {
            $this->assertDatabaseHas('student_notes', [
                'student_id'        => $student->id,
                'teacher_id'        => $teacher->id,
                'lesson_session_id' => $session->id,
                'kind'              => 'weakness',
                'topic_tag'         => 'геометрия',
                'source'            => 'chat',
                'body'              => $text, // ОРИГИНАЛЬНЫЙ текст в БД
            ]);
        }

        // Имена учеников анонимизированы — в API не ушли
        Http::assertSent(fn ($r) => ! str_contains($r->body(), 'Петя')
            && ! str_contains($r->body(), 'Вася'));
    }

    public function test_record_note_falls_back_to_general_when_api_fails(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/chat/completions' => Http::response(['error' => 'boom'], 500),
        ]);

        $teacher = $this->teacher();
        $petya = $this->student('Петя');
        $vasya = $this->student('Вася');
        $session = $this->sessionWith($teacher, $petya, $vasya);

        $result = app(AssistantService::class)
            ->recordNote($session, $teacher, [$petya->id, $vasya->id], 'путают подобие');

        // Fallback: ученики известны явно — записи всё равно создаются
        $this->assertSame('general', $result['kind']);
        $this->assertNull($result['topic_tag']);
        $this->assertCount(2, $result['notes']);
        $this->assertSame(2, StudentNote::count());
        $this->assertSame(2, StudentNote::where('kind', 'general')->count());
    }

    public function test_record_note_defaults_to_general_when_no_tool_call(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'ок, записал'],
                ]],
            ]),
        ]);

        $teacher = $this->teacher();
        $petya = $this->student('Петя');
        $session = $this->sessionWith($teacher, $petya);

        $result = app(AssistantService::class)
            ->recordNote($session, $teacher, [$petya->id], 'что-то без явного тега');

        $this->assertSame('general', $result['kind']);
        $this->assertNull($result['topic_tag']);
        $this->assertCount(1, $result['notes']);
        $this->assertDatabaseHas('student_notes', [
            'student_id' => $petya->id,
            'kind'       => 'general',
            'topic_tag'  => null,
            'source'     => 'chat',
            'body'       => 'что-то без явного тега',
        ]);
    }
}
