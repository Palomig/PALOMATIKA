<?php

namespace Tests\Unit;

use App\Services\Entrance10Generator;
use App\Services\Entrance10Service;
use PHPUnit\Framework\TestCase;

/**
 * Проверка ответов вступительной работы в 10 класс. Эталоны в bank.json
 * записаны символом корня («1;√12;-√12», «2√3»), поэтому ответы с радикалом
 * должны приниматься в любой равносильной записи.
 */
class Entrance10AnswerCheckTest extends TestCase
{
    private Entrance10Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new Entrance10Service($this->createMock(Entrance10Generator::class));
    }

    /**
     * @dataProvider radicalAnswers
     */
    public function test_radical_answers(string $canonical, string $user, bool $want): void
    {
        $this->assertSame(
            $want,
            $this->service->isCorrect('number_set', $canonical, $user),
            "«{$user}» ↔ «{$canonical}»",
        );
    }

    /** @return array<string, array{0:string,1:string,2:bool}> */
    public static function radicalAnswers(): array
    {
        return [
            'символ корня' => ['2√3', '2√3', true],
            'sqrt без скобок' => ['2√3', '2sqrt3', true],
            'sqrt со скобками' => ['2√3', '2*sqrt(3)', true],
            'слово корень' => ['2√3', '2 корень из 3', true],
            'неупрощённая форма' => ['2√3', '√12', true],
            'набор корней' => ['1;√12;-√12', '1; 2√3; -2√3', true],
            'набор в другом порядке' => ['-2;√6;-√6', '√6; -√6; -2', true],
            'набор через пробел' => ['-2;√6;-√6', '-2 √6 -√6', true],
            'плюс-минус' => ['√6;-√6', '±√6', true],
            'потерян корень' => ['1;√12;-√12', '1; 2√3', false],
            'лишний корень' => ['√6;-√6', '√6; -√6; 0', false],
            'приближение' => ['2√3', '3.46', false],
            'просто неверно' => ['2√3', '2√5', false],
        ];
    }

    /** Обычные числовые ответы работают как раньше. */
    public function test_plain_numeric_answers_still_work(): void
    {
        $this->assertTrue($this->service->isCorrect('number', '12', '12'));
        $this->assertTrue($this->service->isCorrect('number', '1.5', '1,5'));
        $this->assertTrue($this->service->isCorrect('number', '-0.75', '-3/4'));
        $this->assertFalse($this->service->isCorrect('number', '12', '13'));
        $this->assertFalse($this->service->isCorrect('number', '12', ''));
    }

    /** Прочие режимы проверки не затронуты. */
    public function test_other_check_modes_are_untouched(): void
    {
        $this->assertTrue($this->service->isCorrect('param_condition', 'b ≠ 1', 'b != 1'));
        $this->assertFalse($this->service->isCorrect('param_condition', 'b ≠ 1', 'b > 1'));
        $this->assertTrue($this->service->isCorrect('yesno', 'да', 'Да'));
        $this->assertFalse($this->service->isCorrect('yesno', 'да', 'нет'));
    }

    /**
     * Условия на параметр набираются панелью символов, которая вставляет
     * «≠ ≥ ≤» — сервер должен принимать их наравне с ASCII-записью.
     *
     * @dataProvider conditionAnswers
     */
    public function test_param_condition_accepts_pad_symbols(string $canonical, string $user, bool $want): void
    {
        $this->assertSame(
            $want,
            $this->service->isCorrect('param_condition', $canonical, $user),
            "«{$user}» ↔ «{$canonical}»",
        );
    }

    /** @return array<string, array{0:string,1:string,2:bool}> */
    public static function conditionAnswers(): array
    {
        return [
            'символ с панели' => ['p ≠ 9', 'p ≠ 9', true],
            'ascii-запись' => ['p ≠ 9', 'p != 9', true],
            'другая переменная' => ['p ≠ 9', 'b ≠ 9', true],
            'два условия' => ['p > 0, p ≠ 9', 'p > 0, p ≠ 9', true],
            'два условия в другом порядке' => ['p > 0, p ≠ 9', 'p ≠ 9, p > 0', true],
            'нестрогий знак с панели' => ['p ≥ 0', 'p ≥ 0', true],
            'нестрогий знак ascii' => ['p ≥ 0', 'p >= 0', true],
            'строгий вместо нестрогого' => ['p ≥ 0', 'p > 0', false],
            'потеряно условие' => ['p > 0, p ≠ 9', 'p > 0', false],
            'другое число' => ['p ≠ 9', 'p ≠ 8', false],
        ];
    }
}
