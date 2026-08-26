<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\OgeVariant;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\LessonTaskPickerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Урок и домашка — единый инструмент для всех классов, а не только для ОГЭ:
 * плитка урока на каждом дашборде, ДЗ у всех, задачи выбираются для 5–11.
 */
class LessonAndHomeworkForAllGradesTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response([], 200)]);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
    }

    private function student(int $grade): User
    {
        $student = User::factory()->create([
            'role' => 'student',
            'grade_num' => $grade,
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => 100000 + $grade,
        ]);
        TeacherStudent::create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
        ]);

        return $student;
    }

    private function studentBase(): string
    {
        return 'https://student.' . config('app.base_domain');
    }

    /**
     * Дашборд ученика зависит от класса: 5–8 → ВПР, 9 → ОГЭ, 10–11 → ЕГЭ.
     * Плитка урока должна быть на каждом.
     */
    public static function gradeProvider(): array
    {
        return [
            '5 класс (ВПР)' => [5],
            '6 класс (ВПР)' => [6],
            '8 класс (ВПР)' => [8],
            '9 класс (ОГЭ)' => [9],
            '10 класс (ЕГЭ)' => [10],
            '11 класс (ЕГЭ)' => [11],
        ];
    }

    /** @dataProvider gradeProvider */
    public function test_lesson_tile_is_on_every_dashboard(int $grade): void
    {
        $student = $this->student($grade);

        $html = $this->dashboardHtml($student);

        $this->assertStringContainsString('УРОК', $html, "У {$grade} класса нет плитки урока");
        $this->assertStringContainsString('lessonTile()', $html);
    }

    /** @dataProvider gradeProvider */
    public function test_homework_is_reachable_from_every_dashboard(int $grade): void
    {
        $student = $this->student($grade);

        $html = $this->dashboardHtml($student);

        $this->assertStringContainsString('Домашка', $html, "У {$grade} класса нет входа в домашку");

        // И сама страница домашки открывается.
        $this->actingAs($student)->get($this->studentBase() . '/homework')->assertOk();
    }

    /** Дашборд зависит от класса: 5–8 → ВПР, 9 → сразу ОГЭ, 10–11 → ЕГЭ. */
    private function dashboardHtml(User $student): string
    {
        $response = $this->actingAs($student)->get($this->studentBase() . '/');

        if ($response->isRedirect()) {
            $response = $this->actingAs($student)->get($response->headers->get('Location'));
        }

        return $response->assertOk()->getContent();
    }

    public function test_picker_offers_every_grade_and_bank(): void
    {
        $html = $this->actingAs($this->teacher)
            ->get('https://teacher.' . config('app.base_domain') . '/homework')
            ->assertOk()
            ->getContent();

        // Список классов уезжает в страницу как JSON, поэтому кириллица в \uXXXX.
        foreach (['5 \u043a\u043b\u0430\u0441\u0441', '6 \u043a\u043b\u0430\u0441\u0441', '7 \u043a\u043b\u0430\u0441\u0441', '9 \u041e\u0413\u042d'] as $label) {
            $this->assertStringContainsString($label, $html, "В picker'е нет класса «{$label}»");
        }
        // Банки ВПР и ЕГЭ доехали до интерфейса.
        $this->assertStringContainsString('"bank":"vpr"', $html);
        $this->assertStringContainsString('"bank":"ege"', $html);
        // 8 класс не предлагаем: в его банках нет ни одной задачи.
        $this->assertStringNotContainsString('"label":"8 \u043a\u043b\u0430\u0441\u0441"', $html);
    }

    /** Бэкенд picker'а должен отдавать темы для каждого банка. */
    public function test_picker_options_return_topics_for_all_banks(): void
    {
        $base = 'https://teacher.' . config('app.base_domain') . '/lessons/picker-options';

        $oge = $this->actingAs($this->teacher)->getJson($base . '?bank=oge')->assertOk()->json();
        $this->assertNotEmpty($oge['sections'] ?? [], 'ОГЭ без разделов');

        $vpr = $this->actingAs($this->teacher)->getJson($base . '?bank=vpr&grade=6')->assertOk()->json();
        $this->assertNotEmpty($vpr['topics'] ?? [], 'ВПР 6 класса без тем');

        $ege = $this->actingAs($this->teacher)->getJson($base . '?bank=ege')->assertOk()->json();
        $this->assertNotEmpty($ege['topics'] ?? [], 'ЕГЭ без тем');

        $alg = $this->actingAs($this->teacher)->getJson($base . '?bank=alg-skill&grade=7')->assertOk()->json();
        $this->assertNotEmpty($alg['skills'] ?? [], 'Навыки 7 класса пусты');
    }

    public function test_picker_service_covers_grades_five_to_eleven(): void
    {
        $picker = app(LessonTaskPickerService::class);

        $this->assertSame([5, 6, 7, 8], $picker->grades('vpr'));
        $this->assertSame([9], $picker->grades('oge'));
        $this->assertSame([11], $picker->grades('ege'));

        // Задачи реально достаются из каждого банка.
        $this->assertNotEmpty($picker->tasks('vpr', ['grade' => 5, 'topic_id' => $picker->topics('vpr', 5)[0]['id']]));
        $this->assertNotEmpty($picker->tasks('ege', ['topic_id' => $picker->topics('ege')[0]['id']]));
        $this->assertNotEmpty($picker->skills(7), 'Навыки 7 класса пусты');

        // Классы приходят из данных: 5, 6, 7, 9 ОГЭ и ЕГЭ есть, пустого 8-го нет.
        $labels = array_column($picker->availableClasses(), 'label');
        $this->assertSame(['5 класс', '6 класс', '7 класс', '9 ОГЭ', '10–11 ЕГЭ (П)'], $labels);
    }

    public function test_senior_student_gets_ege_variant_not_oge(): void
    {
        $eleventh = $this->student(11);

        $this->actingAs($this->teacher)->post('https://teacher.' . config('app.base_domain') . '/homework/assign', [
            'student_id' => $eleventh->id,
            'type' => 'mini_variant',
        ])->assertRedirect()->assertSessionHas('success');

        $homework = Homework::latest('id')->first();
        $this->assertNotNull($homework);
        $this->assertSame('Вариант ЕГЭ', $homework->title);

        $variant = OgeVariant::where('hash', $homework->variant_hash)->firstOrFail();
        $this->assertSame('ege', $variant->exam_type, 'Одиннадцатикласснику ушёл не тот экзамен');
    }

    public function test_middle_grades_still_get_vpr_variant(): void
    {
        $sixth = $this->student(6);

        $this->actingAs($this->teacher)->post('https://teacher.' . config('app.base_domain') . '/homework/assign', [
            'student_id' => $sixth->id,
            'type' => 'mini_variant',
        ])->assertRedirect()->assertSessionHas('success');

        $homework = Homework::latest('id')->first();
        $this->assertSame('Мини-ВПР 6 класс', $homework->title);
        $this->assertSame('vpr_6', OgeVariant::where('hash', $homework->variant_hash)->firstOrFail()->exam_type);
    }
}
