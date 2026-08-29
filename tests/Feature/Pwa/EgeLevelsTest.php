<?php

namespace Tests\Feature\Pwa;

use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EgeLevelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_columns_exist_and_are_mass_assignable(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'ege_level'));
        $this->assertTrue(Schema::hasColumn('oge_variants', 'level'));

        $user = User::factory()->create(['ege_level' => 'base']);
        $variant = OgeVariant::create([
            'hash' => 'egelvl',
            'exam_type' => OgeVariant::EXAM_EGE,
            'level' => 'base',
            'title' => 'Вариант ЕГЭ (Б)',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'config_json' => ['level' => 'base', 'tasks' => []],
            'mode' => OgeVariant::MODE_FULL,
        ]);

        $this->assertSame('base', $user->fresh()->ege_level);
        $this->assertSame('base', $variant->fresh()->level);
    }
}
