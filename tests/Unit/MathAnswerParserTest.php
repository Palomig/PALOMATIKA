<?php

namespace Tests\Unit;

use App\Services\MathAnswerParser;
use PHPUnit\Framework\TestCase;

class MathAnswerParserTest extends TestCase
{
    private MathAnswerParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MathAnswerParser();
    }

    /**
     * @dataProvider equivalentAnswers
     */
    public function test_equivalent_notations_are_accepted(string $expected, string $user): void
    {
        $this->assertTrue(
            $this->parser->setsMatch($expected, $user),
            "«{$user}» должен засчитываться как «{$expected}»",
        );
    }

    /** @return array<string, array{0:string,1:string}> */
    public static function equivalentAnswers(): array
    {
        return [
            'символ корня' => ['12√6', '12√6'],
            'sqrt со скобками' => ['12√6', '12*sqrt(6)'],
            'sqrt без скобок' => ['√6', 'sqrt6'],
            'слово корень' => ['12√6', '12 корень из 6'],
            'неупрощённая форма' => ['12√6', 'sqrt(864)'],
            'latex-эталон' => ['\sqrt{2}', '√2'],
            'коэффициент вынесен' => ['√12', '2√3'],
            'дробь с корнем' => ['√6/2', 'sqrt(6)/2'],
            'составное выражение' => ['(3+√5)/2', '(3 + sqrt(5))/2'],
            'обёртка x =' => ['2√3', 'x = 2√3'],
            'префикс ответ' => ['8', 'Ответ: 8'],
            'единицы длины' => ['12√6', '12√6 см'],
            'единицы площади' => ['12√6', '12√6 кв. см'],
            'градусы' => ['45', '45°'],
            'множество через ;' => ['-4 - sqrt(7); -4 + sqrt(7)', '-4+√7; -4-√7'],
            'множество через пробел' => ['1;√12;-√12', '1 √12 -√12'],
            'множество через запятую' => ['√6;-√6', '√6, -√6'],
            'множество через и' => ['√6;-√6', '√6 и -√6'],
            'плюс-минус' => ['√6;-√6', '±√6'],
            'плюс-минус с коэффициентом' => ['-4 - sqrt(7); -4 + sqrt(7)', '-4±√7'],
            'десятичная запятая' => ['1.5', '1,5'],
            'обыкновенная дробь' => ['1.5', '3/2'],
            'юникодный минус' => ['-√6', '−√6'],
            'дубли в перечислении' => ['√6', '√6; √6'],
        ];
    }

    /**
     * @dataProvider wrongAnswers
     */
    public function test_wrong_answers_are_rejected(string $expected, string $user): void
    {
        $this->assertNotTrue(
            $this->parser->setsMatch($expected, $user),
            "«{$user}» не должен засчитываться как «{$expected}»",
        );
    }

    /** @return array<string, array{0:string,1:string}> */
    public static function wrongAnswers(): array
    {
        return [
            'другое число' => ['12√6', '12√5'],
            'десятичное приближение' => ['12√6', '29.39'],
            'потерян корень множества' => ['-4 - sqrt(7); -4 + sqrt(7)', '-4-√7'],
            'лишний корень' => ['√6;-√6', '√6; -√6; 0'],
            'знак' => ['2√3', '-2√3'],
            'пустая строка' => ['2√3', ''],
        ];
    }

    public function test_unparsable_input_returns_null(): void
    {
        $this->assertNull($this->parser->setsMatch('2√3', 'не знаю'));
        $this->assertNull($this->parser->setsMatch('2√3', '2√3 кг'));
    }

    public function test_negative_radicand_is_not_a_number(): void
    {
        $this->assertNull($this->parser->value('√(-4)'));
    }

    public function test_division_by_zero_is_not_a_number(): void
    {
        $this->assertNull($this->parser->value('5/0'));
    }

    public function test_has_radical_detects_all_notations(): void
    {
        foreach (['12√6', '2*sqrt(3)', '\sqrt{2}', 'корень из 5'] as $value) {
            $this->assertTrue($this->parser->hasRadical($value), $value);
        }
        foreach (['17', '1.5', '3/4', '-25'] as $value) {
            $this->assertFalse($this->parser->hasRadical($value), $value);
        }
    }

    public function test_decimal_approximation_is_recognised(): void
    {
        $this->assertTrue($this->parser->looksLikeDecimalApproximation('4.6457'));
        $this->assertTrue($this->parser->looksLikeDecimalApproximation('2,45'));
        $this->assertFalse($this->parser->looksLikeDecimalApproximation('2√3'));
        $this->assertFalse($this->parser->looksLikeDecimalApproximation('17'));
    }

    public function test_ambiguous_spacing_reads_both_ways(): void
    {
        // «√6 -√6» — и разность, и перечисление; принимаем оба прочтения.
        $this->assertTrue($this->parser->setsMatch('0', '√6 -√6'));
        $this->assertTrue($this->parser->setsMatch('√6; -√6', '√6 -√6'));
    }

    /**
     * @dataProvider intervalAnswers
     */
    public function test_interval_answers(string $expected, string $user, ?bool $want): void
    {
        $this->assertSame($want, $this->parser->answersMatch($expected, $user), "«{$user}» ↔ «{$expected}»");
    }

    /** @return array<string, array{0:string,1:string,2:?bool}> */
    public static function intervalAnswers(): array
    {
        return [
            'та же запись' => ['(1; 1 + \sqrt{2})', '(1; 1+√2)', true],
            'sqrt вместо символа' => ['(1; 1 + \sqrt{2})', '(1;1+sqrt(2))', true],
            'обе границы с корнем' => ['(5 - \sqrt{2}; 5 + \sqrt{2})', '(5-√2; 5+√2)', true],
            'скобка другого типа' => ['(1; 1 + \sqrt{2})', '[1; 1+√2]', false],
            'приближение границы' => ['(1; 1 + \sqrt{2})', '(1; 2.41)', false],
            'объединение' => ['(-∞; -√5) ∪ (√5; +∞)', '(-∞;-√5)∪(√5;+∞)', true],
            'объединение в другом порядке' => ['(-∞; -√5) ∪ (√5; +∞)', '(√5; +∞) U (-∞; -√5)', true],
            'бесконечность словом' => ['(-∞; 2)', '(-бесконечность; 2)', true],
            'составная граница' => ['((3+√5)/2; 4)', '((3+√5)/2; 4)', true],
            'без скобок промежутка' => ['(1; 1 + \sqrt{2})', '1; 1+√2', null],
        ];
    }

    public function test_value_evaluates_single_expression(): void
    {
        $this->assertEqualsWithDelta(2 * sqrt(3), $this->parser->value('2√3'), 1e-9);
        $this->assertEqualsWithDelta((3 + sqrt(5)) / 2, $this->parser->value('(3+√5)/2'), 1e-9);
        $this->assertEqualsWithDelta(9.0, $this->parser->value('3^2'), 1e-9);
    }
}
