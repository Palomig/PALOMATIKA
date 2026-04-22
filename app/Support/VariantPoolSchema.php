<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

final class VariantPoolSchema
{
    private static ?bool $hasExamTypeColumn = null;

    public static function hasExamTypeColumn(): bool
    {
        if (self::$hasExamTypeColumn !== null) {
            return self::$hasExamTypeColumn;
        }

        self::$hasExamTypeColumn = Schema::hasTable('oge_variant_pool')
            && Schema::hasColumn('oge_variant_pool', 'exam_type');

        return self::$hasExamTypeColumn;
    }

    public static function reset(): void
    {
        self::$hasExamTypeColumn = null;
    }
}
