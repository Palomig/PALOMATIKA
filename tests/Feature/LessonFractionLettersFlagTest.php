<?php

namespace Tests\Feature;

use App\Models\LessonSessionTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Виджет «записать дробью» открывает буквенную клавиатуру по признаку
 * letters: в банке «Скиллы» ответ бывает «(a+5)/(a-5)». Сам эталон
 * ученику не уходит — только признак.
 */
class LessonFractionLettersFlagTest extends TestCase
{
    use RefreshDatabase;

    private const TEACHER_BASE = 'http://teacher.palomatika.ru';
    private const STUDENT_BASE = 'http://student.palomatika.ru';

    public function test_state_marks_letter_answers_without_leaking_them(): void
    {
        $teacher = User::create(['name' => 'T', 'email' => 't' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher']);
        $student = User::create(['name' => 'S', 'email' => 's' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'student',
            'onboarding_completed_at' => now(), 'telegram_chat_id' => random_int(100000000, 999999999)]);

        $sessionId = $this->actingAs($teacher)->postJson(self::TEACHER_BASE . '/lessons')->assertCreated()->json('session.id');

        foreach ([['5/11', 1], ['(a+5)/(a-5)', 2]] as [$answer, $position]) {
            LessonSessionTask::create([
                'lesson_session_id' => $sessionId,
                'position' => $position,
                'bank' => 'skills',
                'task_ref' => json_encode(['task_id' => $position]),
                'task_payload' => ['type' => 'expression', 'expression' => 'x', 'answer' => $answer],
                'correct_answer' => $answer,
            ]);
        }

        $code = $this->actingAs($teacher)->postJson(self::TEACHER_BASE . "/lessons/{$sessionId}/start")
            ->assertOk()->json('session.join_code');
        $this->actingAs($student)->postJson(self::STUDENT_BASE . '/lessons/join', ['code' => $code])->assertOk();

        $resp = $this->actingAs($student)->getJson(self::STUDENT_BASE . "/lessons/{$sessionId}/state")->assertOk();
        $tasks = collect($resp->json('tasks'))->sortBy('position')->values();

        $this->assertFalse($tasks[0]['letters']);
        $this->assertTrue($tasks[1]['letters']);
        $this->assertStringNotContainsString('a+5', $resp->getContent());
    }
}
