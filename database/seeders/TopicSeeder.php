<?php

namespace Database\Seeders;

use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TopicSeeder extends Seeder
{
    public function run(): void
    {
        $topics = [
            ['oge_number' => '01-05', 'name' => 'Практические задачи', 'description' => 'Задачи на бытовую математику: расчёт материалов, площадей, стоимости'],
            ['oge_number' => '06', 'name' => 'Числа и вычисления', 'description' => 'Действия с рациональными числами, свойства чисел'],
            ['oge_number' => '07', 'name' => 'Алгебраические выражения', 'description' => 'Преобразование выражений, формулы сокращённого умножения'],
            ['oge_number' => '08', 'name' => 'Уравнения', 'description' => 'Линейные и квадратные уравнения'],
            ['oge_number' => '09', 'name' => 'Неравенства', 'description' => 'Линейные и квадратные неравенства, системы неравенств'],
            ['oge_number' => '10', 'name' => 'Последовательности', 'description' => 'Арифметическая и геометрическая прогрессии'],
            ['oge_number' => '11', 'name' => 'Функции и графики', 'description' => 'Свойства функций, чтение графиков'],
            ['oge_number' => '12', 'name' => 'Графики функций', 'description' => 'Построение и анализ графиков'],
            ['oge_number' => '13', 'name' => 'Расчёты по формулам', 'description' => 'Вычисления по физическим и математическим формулам'],
            ['oge_number' => '14', 'name' => 'Геометрия: углы', 'description' => 'Углы, их виды и свойства'],
            ['oge_number' => '15', 'name' => 'Геометрия: треугольники', 'description' => 'Свойства треугольников, теорема Пифагора'],
            ['oge_number' => '16', 'name' => 'Геометрия: четырёхугольники', 'description' => 'Параллелограмм, прямоугольник, ромб, трапеция'],
            ['oge_number' => '17', 'name' => 'Геометрия: окружность', 'description' => 'Окружность, касательные, хорды, вписанные углы'],
            ['oge_number' => '18', 'name' => 'Площади', 'description' => 'Вычисление площадей фигур'],
            ['oge_number' => '19', 'name' => 'Анализ геометрических высказываний', 'description' => 'Определение истинности геометрических утверждений'],
            ['oge_number' => '20', 'name' => 'Статистика и вероятность', 'description' => 'Среднее арифметическое, медиана, мода, вероятность'],
            ['oge_number' => '21', 'name' => 'Уравнения и системы (часть 2)', 'description' => 'Сложные уравнения и системы уравнений'],
            ['oge_number' => '22', 'name' => 'Текстовые задачи (часть 2)', 'description' => 'Задачи на движение, работу, смеси'],
            ['oge_number' => '23', 'name' => 'Функции и графики (часть 2)', 'description' => 'Построение графиков, преобразования'],
            ['oge_number' => '24', 'name' => 'Геометрическая задача', 'description' => 'Вычислительная задача по геометрии'],
            ['oge_number' => '25', 'name' => 'Геометрическое доказательство', 'description' => 'Задача на доказательство'],
        ];

        foreach ($topics as $index => $topicData) {
            Topic::create([
                'oge_number' => $topicData['oge_number'],
                'name' => $topicData['name'],
                'slug' => Str::slug($topicData['name']),
                'description' => $topicData['description'],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }
}
