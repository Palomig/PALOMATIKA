<?php

namespace Tests\Unit;

use App\Services\TaskAnswerResolver;
use PHPUnit\Framework\TestCase;

/**
 * Эталоны «H или L» (ВПР 8, графы) и «-1012 или -1210» (два верных ответа
 * текстовой задачи): засчитывается любой из вариантов.
 */
class AlternativeAnswersTest extends TestCase
{
    public function test_any_alternative_is_accepted(): void
    {
        $resolver = new TaskAnswerResolver();

        $this->assertTrue($resolver->isCorrect('H', 'H или L'));
        $this->assertTrue($resolver->isCorrect('L', 'H или L'));
        $this->assertFalse($resolver->isCorrect('K', 'H или L'));
        $this->assertTrue($resolver->isCorrect('-1210', '-1012 или -1210'));
        $this->assertTrue($resolver->isCorrect('−1012', '-1012 или -1210'));
        $this->assertFalse($resolver->isCorrect('1012', '-1012 или -1210'));
    }

    public function test_cyrillic_lookalike_of_latin_letter_is_accepted(): void
    {
        $resolver = new TaskAnswerResolver();

        // С телефонной раскладки «А» и «Н» набираются кириллицей.
        $this->assertTrue($resolver->isCorrect('А', 'A'));
        $this->assertTrue($resolver->isCorrect('а', 'A'));
        $this->assertTrue($resolver->isCorrect('Н', 'H или L'));
        $this->assertFalse($resolver->isCorrect('Б', 'A'));
    }

    public function test_compound_answers_with_ili_are_not_split(): void
    {
        $resolver = new TaskAnswerResolver();

        // Ответы с параметром из прежнего банка ОГЭ: « или » там — часть
        // записи ответа, а не перечень вариантов.
        $this->assertFalse($resolver->isCorrect('$a = 0$', '$a = 0$ или $a = -1$'));
        $this->assertFalse($resolver->isCorrect('x < 3', 'x < 3 или x > 3'));
    }

    public function test_absolute_value_bar_is_not_an_alternative(): void
    {
        $resolver = new TaskAnswerResolver();

        $this->assertFalse($resolver->isCorrect('ln', 'ln|x|+C'));
    }
}
