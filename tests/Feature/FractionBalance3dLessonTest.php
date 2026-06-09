<?php

namespace Tests\Feature;

use Tests\TestCase;

class FractionBalance3dLessonTest extends TestCase
{
    public function test_public_fraction_balance_3d_lesson_opens(): void
    {
        $this->get('/learn/fractions')
            ->assertOk()
            ->assertSee('data-fraction-balance-3d', false)
            ->assertSee('fraction-balance-3d.js', false)
            ->assertSee('Умножаем обе части на 2', false)
            ->assertSee('Только слева', false)
            ->assertSee('Обе части', false);
    }
}
