<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jarvis_materials', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->after('excerpt');
        });

        // Seed categories for existing materials
        $seeds = [
            1 => 'Задачи на движение',
            2 => 'Модуль',
            3 => 'Модуль',
            4 => 'Модуль',
            5 => 'Модуль',
            6 => 'Модуль',
            7 => 'Прогрессии',
        ];

        foreach ($seeds as $id => $category) {
            DB::table('jarvis_materials')->where('id', $id)->update(['category' => $category]);
        }
    }

    public function down(): void
    {
        Schema::table('jarvis_materials', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
