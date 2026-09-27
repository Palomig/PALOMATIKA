<?php

namespace Tests\Unit;

use App\Services\AlgebraicAnswerComparator;
use App\Services\TaskAnswerResolver;
use PHPUnit\Framework\TestCase;

/**
 * Сверка буквенных ответов значением, а не записью.
 *
 * Ученик пишет сокращённую дробь как умеет, и все эти записи — один ответ.
 * Обратное тоже обязано работать: похоже выглядящая, но другая дробь должна
 * считаться ошибкой, иначе проверка бесполезна.
 */
class AlgebraicAnswerComparatorTest extends TestCase
{
    private AlgebraicAnswerComparator $cmp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cmp = new AlgebraicAnswerComparator();
    }

    public static function sameAnswers(): array
    {
        return [
            'скобки вокруг знаменателя'  => ['2x/(3y)', '2x/3y'],
            'скобки вокруг числителя'    => ['2x/(3y)', '(2x)/(3y)'],
            'явное умножение'            => ['2x/(3y)', '2*x/(3*y)'],
            'дробь в LaTeX'              => ['2x/(3y)', '\frac{2x}{3y}'],
            'кириллица вместо латиницы'  => ['2x/(3y)', '2х/(3у)'],
            'минус вынесен'              => ['-(a+5)/3', '(-a-5)/3'],
            'слагаемые переставлены'     => ['-(a+5)/3', '-(5+a)/3'],
            'скобки раскрыты'            => ['8(a+b)/(b-a)', '(8a+8b)/(b-a)'],
            'знак перенесён в знаменатель' => ['8(a+b)/(b-a)', '-8(a+b)/(a-b)'],
            'степень значком'            => ['7c^2x^4/2', '7c²x⁴/2'],
            'двоеточие вместо дроби'     => ['1/(a-3)', '1 : (a-3)'],
            'пробелы'                    => ['(m+10)/(m-10)', '(m + 10) / (m - 10)'],
            'одночлен'                   => ['3x', 'x*3'],
        ];
    }

    /** @dataProvider sameAnswers */
    public function test_same_answer_written_differently_is_accepted(string $reference, string $user): void
    {
        $this->assertTrue($this->cmp->equals($reference, $user),
            "«{$user}» — это «{$reference}», записанный иначе");
    }

    public static function differentAnswers(): array
    {
        return [
            'перевёрнутая дробь'      => ['2x/(3y)', '3y/(2x)'],
            'потерян знак'            => ['-(a+5)/3', '(a+5)/3'],
            'другой коэффициент'      => ['2x/(3y)', '2x/(5y)'],
            'другая буква'            => ['2x/(3y)', '2a/(3b)'],
            'не сокращено до конца'   => ['(m+10)/(m-10)', '(m^2-100)/(m-10)'],
            'минус не там'            => ['(x-7)/x', '(7-x)/x'],
            'перепутана степень'      => ['7c^2x^4/2', '7c^4x^2/2'],
            'мусор вместо ответа'     => ['2x/(3y)', 'не знаю'],
        ];
    }

    /** @dataProvider differentAnswers */
    public function test_different_answer_is_rejected(string $reference, string $user): void
    {
        $this->assertFalse($this->cmp->equals($reference, $user),
            "«{$user}» не равно «{$reference}»");
    }

    /** Числовые ответы ОГЭ и ЕГЭ проходят мимо буквенной ветки. */
    public function test_numeric_answers_are_not_algebraic(): void
    {
        foreach (['0,9', '-12', '2/3', '13π/4; 23π/6', '2√6', '[-6;-4]∪[3;5]', 'нет в базе'] as $answer) {
            $this->assertFalse($this->cmp->looksAlgebraic($answer), "«{$answer}» не буквенный ответ");
        }
        foreach (['2x/(3y)', 'b-2', '-y/z', '(a+c)/(a-x)'] as $answer) {
            $this->assertTrue($this->cmp->looksAlgebraic($answer), "«{$answer}» буквенный ответ");
        }
    }

    /** Та же логика через общий резолвер: именно он решает судьбу домашки. */
    public function test_resolver_accepts_equivalent_algebraic_answer(): void
    {
        $resolver = new TaskAnswerResolver();

        $this->assertTrue($resolver->isCorrect('2x/3y', '2x/(3y)'));
        $this->assertTrue($resolver->isCorrect('(a + 5)/(a - 5)', '(a+5)/(a-5)'));
        $this->assertFalse($resolver->isCorrect('(a-5)/(a+5)', '(a+5)/(a-5)'));
        // Числовая сверка не сломалась.
        $this->assertTrue($resolver->isCorrect('0.9', '0,9'));
        $this->assertTrue($resolver->isCorrect('1 5/7', '12/7'));
    }
}
