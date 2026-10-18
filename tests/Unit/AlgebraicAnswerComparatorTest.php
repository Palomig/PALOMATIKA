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
            'многочлен, другой порядок'  => ['x^2+6x+9', '9+6x+x^2'],
            'многочлен, степень значком' => ['x^2+6x+9', 'x²+6x+9'],
            'многочлен, степень в LaTeX' => ['x^2+6x+9', 'x^{2}+6x+9'],
            'множители переставлены'     => ['(x-3)(x+3)', '(x+3)(x-3)'],
            'квадрат как произведение'   => ['(a+1)^2', '(a+1)(a+1)'],
            'общий множитель вынесен'    => ['3(x-2)(x+2)', '3(x+2)(x-2)'],
            'лишние внешние скобки'      => ['(x-3)(x+3)', '((x-3)(x+3))'],
            // Поле дроби на уроке отдаёт знаменатель без скобок.
            'минус в знаменателе'        => ['-11p/(9n^3)', '11p/-9n³'],
            'минус в знаменателе, буквы' => ['-4d/(3b)', '4d/-3b'],
            'единица в числителе'        => ['z/(3p)', '1z/3p'],
            'знаменатель 1 в поле дроби' => ['6c', '6c/1'],
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
            // Форма ответа: значение то же, а задание не сделано.
            'скобки не раскрыты'      => ['x^2+6x+9', '(x+3)^2'],
            'подобные не приведены'   => ['x^2+6x+9', 'x^2+3x+3x+9'],
            'условие вместо ответа'   => ['12x', '(x+3)^2-(x-3)^2'],
            'не разложено'            => ['(x-3)(x+3)', 'x^2-9'],
            'не разложено в скобках'  => ['(x-3)(x+3)', '(x^2-9)'],
            'разложено не до конца'   => ['3(x-2)(x+2)', '3(x^2-4)'],
            // Урок 16.10: равная дробь, но не сокращённая — задание не сделано.
            'число не сокращено'      => ['z/(3p)', '4z/12p'],
            'число не сокращено 2'    => ['3q/d', '12q/4d'],
            'буква не сокращена'      => ['2/(3nz^2)', '12/18nz²'],
            'общая буква осталась'    => ['3q/d', '3qd/d^2'],
            'многочлен не сокращён'   => ['(a+5)/(a-5)', '(2a+10)/(2a-10)'],
            'дробь вместо одночлена'  => ['3q', '12q/4'],
            'сумма дробей'            => ['(a+5)/3', 'a/3+5/3'],
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

    /** Ответы учеников с урока 16.10 (скриншоты Стаса), как их собрало поле дроби. */
    public function test_lesson_answers_from_october_16(): void
    {
        $resolver = new TaskAnswerResolver();
        $cases = [
            ['z/(3p)', '1z/3p', true], ['z/(3p)', 'z/3p', true], ['z/(3p)', '3z/6p', false],
            ['z/(3p)', '4z/12p', false], ['3q/d', '3q/1d', true], ['3q/d', '12q/4d', false],
            ['3q/d', 'q/d', false], ['2/(3nz^2)', '12/18nz²', false], ['9d/(2q^3)', '9d/2q³', true],
            ['-11p/(9n^3)', '11p/-9n³', true], ['-4d/(3b)', '4d/-3b', true],
        ];
        foreach ($cases as [$reference, $user, $expected]) {
            $this->assertSame($expected, $resolver->isCorrect($user, $reference), "«{$user}» за «{$reference}»");
        }
    }

    /** Числовая дробь: несократимая и в любой записи знака, сократимая — нет. */
    public function test_resolver_requires_reduced_numeric_fraction(): void
    {
        $resolver = new TaskAnswerResolver();

        $this->assertTrue($resolver->isCorrect('7/3', '7/3'));
        $this->assertTrue($resolver->isCorrect('2 1/3', '7/3'));
        $this->assertTrue($resolver->isCorrect('2/-3', '-2/3'));
        $this->assertTrue($resolver->isCorrect('(-2)/(3)', '-2/3'));
        $this->assertTrue($resolver->isCorrect('5/1', '5'));
        $this->assertFalse($resolver->isCorrect('14/6', '7/3'));
        $this->assertFalse($resolver->isCorrect('1 4/3', '7/3'));
        $this->assertFalse($resolver->isCorrect('2 2/6', '7/3'));
        $this->assertFalse($resolver->isCorrect('-4/6', '-2/3'));
    }
}
