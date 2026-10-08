<?php

namespace Tests\Feature;

use App\Models\LessonSessionTask;
use App\Models\User;
use App\Services\TaskBankResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Урок ученика: текст задания отдельно от голой формулы и поле ответа
 * по серии. Эталон ученику по-прежнему не уходит.
 */
class LessonTaskConditionPayloadTest extends TestCase
{
    use RefreshDatabase;

    private const TEACHER_BASE = 'http://teacher.palomatika.ru';
    private const STUDENT_BASE = 'http://student.palomatika.ru';

    public function test_resolver_adds_instruction_and_fraction_field_for_bare_formula(): void
    {
        $r = app(TaskBankResolver::class)->resolve('vpr', ['grade' => 5, 'topic_id' => '01', 'zadanie_number' => 2, 'task_id' => 1]);

        $this->assertSame('Запишите дробь в виде несократимой дроби', $r['instruction']);
        $this->assertSame('fraction', $r['answer_field']);
        $this->assertFalse($r['answer_letters']);
    }

    public function test_student_state_passes_condition_fields_without_answer(): void
    {
        $teacher = User::create(['name' => 'T', 'email' => 't' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher']);
        $student = User::create(['name' => 'S', 'email' => 's' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'student',
            'onboarding_completed_at' => now(), 'telegram_chat_id' => random_int(100000000, 999999999)]);

        $sessionId = $this->actingAs($teacher)->postJson(self::TEACHER_BASE . '/lessons')->assertCreated()->json('session.id');
        $this->actingAs($teacher)->postJson(self::TEACHER_BASE . "/lessons/{$sessionId}/tasks", [
            'bank' => 'vpr',
            'refs' => ['grade' => 5, 'topic_id' => '01', 'zadanie_number' => 2, 'task_id' => 1],
        ])->assertCreated();

        $code = $this->actingAs($teacher)->postJson(self::TEACHER_BASE . "/lessons/{$sessionId}/start")->assertOk()->json('session.join_code');
        $this->actingAs($student)->postJson(self::STUDENT_BASE . '/lessons/join', ['code' => $code])->assertOk();

        $resp = $this->actingAs($student)->getJson(self::STUDENT_BASE . "/lessons/{$sessionId}/state")->assertOk();
        $payload = $resp->json('tasks.0.payload');

        $this->assertSame('Запишите дробь в виде несократимой дроби', $payload['instruction']);
        $this->assertSame('fraction', $payload['answer_field']);
        $this->assertArrayNotHasKey('answer', $payload);
        $this->assertArrayNotHasKey('raw', $payload);
        $this->assertSame('2/3', LessonSessionTask::first()->correct_answer);
    }
}
