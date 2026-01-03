<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            // Алгебра
            [
                'name' => 'Алгебра',
                'category' => 'Алгебра',
                'children' => [
                    ['name' => 'Действия с дробями', 'oge_numbers' => ['01', '02', '03']],
                    ['name' => 'Сложение дробей', 'oge_numbers' => ['01', '02']],
                    ['name' => 'Умножение дробей', 'oge_numbers' => ['01', '02']],
                    ['name' => 'Деление дробей', 'oge_numbers' => ['01', '02']],
                    ['name' => 'Степени и корни', 'oge_numbers' => ['03', '04']],
                    ['name' => 'Свойства степеней', 'oge_numbers' => ['03', '04']],
                    ['name' => 'Квадратные корни', 'oge_numbers' => ['03', '04']],
                    ['name' => 'Линейные уравнения', 'oge_numbers' => ['06', '21']],
                    ['name' => 'Квадратные уравнения', 'oge_numbers' => ['06', '21']],
                    ['name' => 'Дискриминант', 'oge_numbers' => ['06', '21']],
                    ['name' => 'Формула корней', 'oge_numbers' => ['06', '21']],
                    ['name' => 'Теорема Виета', 'oge_numbers' => ['06', '21']],
                    ['name' => 'Системы уравнений', 'oge_numbers' => ['21']],
                    ['name' => 'Неравенства', 'oge_numbers' => ['07', '21']],
                    ['name' => 'Числовые промежутки', 'oge_numbers' => ['07']],
                    ['name' => 'Функции', 'oge_numbers' => ['11', '12', '22']],
                    ['name' => 'Линейная функция', 'oge_numbers' => ['11', '22']],
                    ['name' => 'Квадратичная функция', 'oge_numbers' => ['11', '12', '22']],
                    ['name' => 'Графики функций', 'oge_numbers' => ['11', '12']],
                    ['name' => 'Арифметическая прогрессия', 'oge_numbers' => ['13']],
                    ['name' => 'Геометрическая прогрессия', 'oge_numbers' => ['13']],
                ],
            ],
            // Геометрия
            [
                'name' => 'Геометрия',
                'category' => 'Геометрия',
                'children' => [
                    ['name' => 'Углы', 'oge_numbers' => ['16', '17', '24']],
                    ['name' => 'Смежные углы', 'oge_numbers' => ['16']],
                    ['name' => 'Вертикальные углы', 'oge_numbers' => ['16']],
                    ['name' => 'Треугольники', 'oge_numbers' => ['16', '17', '24', '25']],
                    ['name' => 'Признаки равенства треугольников', 'oge_numbers' => ['24', '25']],
                    ['name' => 'Подобие треугольников', 'oge_numbers' => ['24', '25']],
                    ['name' => 'Теорема Пифагора', 'oge_numbers' => ['17', '24', '25']],
                    ['name' => 'Прямоугольный треугольник', 'oge_numbers' => ['17', '24']],
                    ['name' => 'Тригонометрия', 'oge_numbers' => ['17', '18']],
                    ['name' => 'Синус', 'oge_numbers' => ['17', '18']],
                    ['name' => 'Косинус', 'oge_numbers' => ['17', '18']],
                    ['name' => 'Тангенс', 'oge_numbers' => ['17', '18']],
                    ['name' => 'Четырёхугольники', 'oge_numbers' => ['16', '18', '24']],
                    ['name' => 'Параллелограмм', 'oge_numbers' => ['18', '24']],
                    ['name' => 'Прямоугольник', 'oge_numbers' => ['18', '24']],
                    ['name' => 'Ромб', 'oge_numbers' => ['18', '24']],
                    ['name' => 'Трапеция', 'oge_numbers' => ['18', '24']],
                    ['name' => 'Окружность', 'oge_numbers' => ['19', '24', '25']],
                    ['name' => 'Центральные углы', 'oge_numbers' => ['19']],
                    ['name' => 'Вписанные углы', 'oge_numbers' => ['19', '24']],
                    ['name' => 'Площади фигур', 'oge_numbers' => ['18', '19', '20']],
                    ['name' => 'Площадь треугольника', 'oge_numbers' => ['18', '20']],
                    ['name' => 'Площадь круга', 'oge_numbers' => ['19', '20']],
                    ['name' => 'Координаты на плоскости', 'oge_numbers' => ['14']],
                    ['name' => 'Векторы', 'oge_numbers' => ['14']],
                ],
            ],
            // Практические задачи
            [
                'name' => 'Практические задачи',
                'category' => 'Практика',
                'children' => [
                    ['name' => 'Текстовые задачи', 'oge_numbers' => ['05', '08', '09', '22', '23']],
                    ['name' => 'Задачи на движение', 'oge_numbers' => ['22', '23']],
                    ['name' => 'Задачи на работу', 'oge_numbers' => ['22', '23']],
                    ['name' => 'Задачи на проценты', 'oge_numbers' => ['05', '08']],
                    ['name' => 'Задачи на концентрацию', 'oge_numbers' => ['22', '23']],
                    ['name' => 'Чтение графиков', 'oge_numbers' => ['09', '10']],
                    ['name' => 'Вероятность', 'oge_numbers' => ['10']],
                    ['name' => 'Статистика', 'oge_numbers' => ['15']],
                    ['name' => 'Среднее арифметическое', 'oge_numbers' => ['15']],
                    ['name' => 'Медиана', 'oge_numbers' => ['15']],
                    ['name' => 'Мода', 'oge_numbers' => ['15']],
                ],
            ],
        ];

        $order = 0;
        foreach ($skills as $parentData) {
            $parent = Skill::create([
                'name' => $parentData['name'],
                'slug' => Str::slug($parentData['name']),
                'category' => $parentData['category'],
                'sort_order' => $order++,
                'is_active' => true,
            ]);

            foreach ($parentData['children'] as $childData) {
                Skill::create([
                    'parent_id' => $parent->id,
                    'name' => $childData['name'],
                    'slug' => Str::slug($childData['name']),
                    'category' => $parentData['category'],
                    'oge_numbers' => $childData['oge_numbers'],
                    'sort_order' => $order++,
                    'is_active' => true,
                ]);
            }
        }
    }
}
