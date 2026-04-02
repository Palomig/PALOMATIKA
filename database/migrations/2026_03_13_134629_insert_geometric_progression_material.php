<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $slug = 'geometricheskaia-progressiia-obiiasnenie-i-zadachi';
        $now = now();

        $sourceContent = <<<'CONTENT'
Геометрическая прогрессия: простое объяснение

Что такое геометрическая прогрессия
Геометрическая прогрессия — это последовательность чисел, в которой каждый следующий элемент получается умножением предыдущего на одно и то же число.

Это число называется знаменатель прогрессии и обозначается буквой $q$.

Пример
$$2,\ 6,\ 18,\ 54,\ 162,\ \ldots$$
Здесь каждое число умножается на $3$, значит $q = 3$.

Ещё пример:
$$100,\ 50,\ 25,\ 12{,}5,\ \ldots$$
Здесь каждое число умножается на $0{,}5$, значит $q = 0{,}5$.

Формулы

Формула n-го члена
Если известен первый член $b_1$ и знаменатель $q$, то любой член прогрессии можно найти по формуле:
$$b_n = b_1 \cdot q^{n-1}$$

Например, пусть $b_1 = 3$, $q = 2$. Найдём пятый член:
$$b_5 = 3 \cdot 2^{5-1} = 3 \cdot 2^4 = 3 \cdot 16 = 48$$

Сумма первых n членов
$$S_n = b_1 \cdot \frac{q^n - 1}{q - 1} \quad (q \neq 1)$$

Как найти знаменатель q
Раздели любой член на предыдущий:
$$q = \frac{b_{n+1}}{b_n}$$

Пример: в прогрессии $5,\ 15,\ 45,\ \ldots$
$$q = \frac{15}{5} = 3$$

Задача 1
Дана геометрическая прогрессия: $4,\ 12,\ 36,\ \ldots$
Найди шестой член прогрессии.

Решение:
1) Находим $q$:
$$q = \frac{12}{4} = 3$$
2) Применяем формулу:
$$b_6 = 4 \cdot 3^{6-1} = 4 \cdot 3^5 = 4 \cdot 243 = 972$$

Ответ: $b_6 = 972$

Задача 2
Первый член геометрической прогрессии равен $5$, знаменатель $q = 2$. Найди сумму первых $4$ членов.

Решение:
$$S_4 = 5 \cdot \frac{2^4 - 1}{2 - 1} = 5 \cdot \frac{16 - 1}{1} = 5 \cdot 15 = 75$$

Проверка: выпишем члены и сложим:
$$5 + 10 + 20 + 40 = 75 \quad ✓$$

Ответ: $S_4 = 75$
CONTENT;

        $ownerTeacherId = DB::table('users')
            ->whereIn('role', ['teacher', 'admin'])
            ->orderBy('id')
            ->value('id');

        if (!$ownerTeacherId) {
            $ownerTeacherId = DB::table('users')->insertGetId([
                'name' => 'Palomatika System Teacher',
                'email' => 'system-teacher@palomatika.local',
                'password' => bcrypt(Str::random(32)),
                'role' => 'teacher',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('jarvis_materials')->insert([
            'owner_teacher_id' => $ownerTeacherId,
            'title' => 'Геометрическая прогрессия: объяснение и задачи',
            'slug' => $slug,
            'excerpt' => 'Что такое геометрическая прогрессия, формулы n-го члена и суммы, два разобранных задания с ответами',
            'source_content' => $sourceContent,
            'status' => 'published',
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('jarvis_materials')
            ->where('slug', 'geometricheskaia-progressiia-obiiasnenie-i-zadachi')
            ->delete();
    }
};
