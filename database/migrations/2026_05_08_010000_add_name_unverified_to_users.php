<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Эпик #44 follow-up: задача #17 (валидация имён).
 *
 * Колонка name_unverified ставится в true когда юзер заявил, что его имени
 * нет в стандартном словаре (галочка «моё имя отсутствует в списке»).
 * Учителю UI должен показывать таких как требующих ручной проверки.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'name_unverified')) {
                $table->boolean('name_unverified')->default(false)->after('last_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'name_unverified')) {
                $table->dropColumn('name_unverified');
            }
        });
    }
};
