<?php

namespace Tests\Unit;

use App\Support\VariantPoolSchema;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VariantPoolSchemaTest extends TestCase
{
    protected function tearDown(): void
    {
        VariantPoolSchema::reset();
        Schema::swap($this->app['db.schema']);

        parent::tearDown();
    }

    public function test_has_exam_type_column_returns_true_when_pool_column_exists(): void
    {
        Schema::shouldReceive('hasTable')->once()->with('oge_variant_pool')->andReturn(true);
        Schema::shouldReceive('hasColumn')->once()->with('oge_variant_pool', 'exam_type')->andReturn(true);

        $this->assertTrue(VariantPoolSchema::hasExamTypeColumn());
    }

    public function test_has_exam_type_column_returns_false_when_pool_column_is_missing(): void
    {
        Schema::shouldReceive('hasTable')->once()->with('oge_variant_pool')->andReturn(true);
        Schema::shouldReceive('hasColumn')->once()->with('oge_variant_pool', 'exam_type')->andReturn(false);

        $this->assertFalse(VariantPoolSchema::hasExamTypeColumn());
    }
}
