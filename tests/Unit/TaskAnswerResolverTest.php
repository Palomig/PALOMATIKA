<?php

namespace Tests\Unit;

use App\Services\TaskAnswerResolver;
use PHPUnit\Framework\TestCase;

class TaskAnswerResolverTest extends TestCase
{
    public function test_resolves_matching_set_answer(): void
    {
        $resolver = new TaskAnswerResolver();

        $answer = $resolver->resolveFromVariantTask([
            'is_matching_set' => true,
            'tasks' => [
                ['options' => ['y=x']],
                ['options' => ['y=x^2']],
                ['options' => ['y=2x']],
            ],
            'formulas' => ['y=x^2', 'y=2x', 'y=x'],
        ]);

        $this->assertSame('312', $answer);
    }

    public function test_resolves_statements_answer_from_truthy_flags(): void
    {
        $resolver = new TaskAnswerResolver();

        $answer = $resolver->resolveFromTaskAndZadanie([
            'type' => 'statements',
            'statements' => [
                ['display_number' => 1, 'is_true' => true],
                ['display_number' => 2, 'is_true' => false],
                ['display_number' => 3, 'is_true' => true],
            ],
        ], []);

        $this->assertSame('13', $answer);
    }

    public function test_normalized_numeric_comparison_accepts_decimal_comma(): void
    {
        $resolver = new TaskAnswerResolver();

        $this->assertTrue($resolver->isCorrect('1,50', '1.5'));
        $this->assertTrue($resolver->isCorrect('  003  ', '3'));
        $this->assertFalse($resolver->isCorrect('2', '3'));
    }

    public function test_digit_sequence_comparison_strips_separators(): void
    {
        $resolver = new TaskAnswerResolver();

        $this->assertTrue($resolver->isCorrect('1, 3', '13'));
        $this->assertTrue($resolver->isCorrect('2 4 1', '241'));
        $this->assertFalse($resolver->isCorrect('241', '214'));
    }

    public function test_resolves_latex_with_variable_assignments_in_text_clause(): void
    {
        $resolver = new TaskAnswerResolver();

        $answer = $resolver->resolveFromTaskAndZadanie(
            ['type' => 'expression'],
            ['expression' => '\sqrt{\frac{16a^{14}}{a^8}} \text{ при } a = 3']
        );

        $this->assertSame('108', $answer);
    }

    public function test_resolves_latex_with_sqrt_products_and_coefficients(): void
    {
        $resolver = new TaskAnswerResolver();

        $answer = $resolver->resolveFromTaskAndZadanie(
            ['type' => 'expression'],
            ['expression' => '5\sqrt{11} \cdot 2\sqrt{2} \cdot \sqrt{22}']
        );

        $this->assertSame('220', $answer);
    }

    public function test_returns_null_without_explicit_answer_for_choice_task(): void
    {
        $resolver = new TaskAnswerResolver();

        $answer = $resolver->resolveFromTaskAndZadanie(
            ['type' => 'choice', 'options' => ['A', 'B', 'C']],
            ['options' => ['A', 'B', 'C']]
        );

        $this->assertNull($answer);
    }

    /**
     * Вторая часть, №23: эталон «12sqrt(6)» принимает любую равносильную запись.
     */
    public function test_radical_answer_accepts_equivalent_notations(): void
    {
        $resolver = new TaskAnswerResolver();

        foreach (['12sqrt(6)', '12√6', '12*sqrt(6)', 'sqrt(864)', '12√6 см'] as $user) {
            $this->assertTrue($resolver->isCorrect($user, '12sqrt(6)'), $user);
        }
        $this->assertFalse($resolver->isCorrect('12√5', '12sqrt(6)'));
    }

    /** Иррациональный ответ десятичной дробью не засчитывается. */
    public function test_radical_answer_rejects_decimal_approximation(): void
    {
        $resolver = new TaskAnswerResolver();

        $this->assertFalse($resolver->isCorrect('29.39', '12sqrt(6)'));
        $this->assertFalse($resolver->isCorrect('29,393876', '12sqrt(6)'));
    }

    /** №20: множество корней сверяется без учёта порядка. */
    public function test_radical_root_set_is_order_independent(): void
    {
        $resolver = new TaskAnswerResolver();
        $correct = '-4 - sqrt(7); -4 + sqrt(7)';

        $this->assertTrue($resolver->isCorrect('-4 + √7; -4 - √7', $correct));
        $this->assertTrue($resolver->isCorrect('-4±√7', $correct));
        $this->assertFalse($resolver->isCorrect('-4 - √7', $correct));
    }

    /** №20, неравенство: промежуток сверяется вместе с типом скобок. */
    public function test_radical_interval_answer(): void
    {
        $resolver = new TaskAnswerResolver();
        $correct = '(1; 1 + \sqrt{2})';

        $this->assertTrue($resolver->isCorrect('(1; 1+√2)', $correct));
        $this->assertFalse($resolver->isCorrect('[1; 1+√2]', $correct));
    }

    /**
     * Первая часть не должна измениться: там ответ — целое число или
     * последовательность цифр, и ветка радикалов не включается.
     */
    public function test_plain_answers_are_unaffected(): void
    {
        $resolver = new TaskAnswerResolver();

        $this->assertTrue($resolver->isCorrect('17', '17'));
        $this->assertTrue($resolver->isCorrect(' 17 ', '17'));
        $this->assertFalse($resolver->isCorrect('18', '17'));
        $this->assertTrue($resolver->isCorrect('0.5', '1/2'));
        $this->assertTrue($resolver->isCorrect('231', '231'));
        $this->assertFalse($resolver->isCorrect('2.45', '17'));
    }

    /**
     * Часть 2 профиля ЕГЭ: ответ — набор корней с π, и порядок их записи
     * на экзамене не оценивается. Пока π не было в грамматике, эталон
     * сверялся строкой, и та же тройка в другом порядке шла в ошибки —
     * на проде так вело себя каждое из 45 заданий №13.
     */
    public function test_pi_root_sets_ignore_order(): void
    {
        $resolver = new TaskAnswerResolver();
        $correct = '13π/4;23π/6;25π/6';

        $this->assertTrue($resolver->isCorrect($correct, $correct));
        $this->assertTrue($resolver->isCorrect('25π/6;13π/4;23π/6', $correct));
        $this->assertTrue($resolver->isCorrect('13pi/4; 23pi/6; 25pi/6', $correct));

        $this->assertFalse($resolver->isCorrect('13π/4;23π/6', $correct), 'потерянный корень');
        $this->assertFalse($resolver->isCorrect('13π/4;23π/6;25π/6;π', $correct), 'лишний корень');
        $this->assertFalse($resolver->isCorrect('10.2101;12.0428;13.0900', $correct), 'приближение вместо точного');
    }

    /** Границы промежутка с логарифмом сверяются числом, а не строкой. */
    public function test_logarithmic_interval_bounds(): void
    {
        $resolver = new TaskAnswerResolver();
        $correct = '(0;log_5(3)]∪[log_3(5);2)';

        $this->assertTrue($resolver->isCorrect($correct, $correct));
        $this->assertTrue($resolver->isCorrect('[log_3(5);2)∪(0;log_5(3)]', $correct));
        $this->assertFalse($resolver->isCorrect('(0;log_5(3))∪[log_3(5);2)', $correct), 'другая скобка');
    }

    /** Обратная тригонометрия: запись отличается, значение одно. */
    public function test_inverse_trigonometry_notations(): void
    {
        $resolver = new TaskAnswerResolver();

        $this->assertTrue($resolver->isCorrect('arctan(5/3)', 'arctg(5/3)'));
        $this->assertTrue($resolver->isCorrect('arctg(5/3)', 'arctg(5/3)'));
        $this->assertFalse($resolver->isCorrect('arctg(3/5)', 'arctg(5/3)'));
    }
}
