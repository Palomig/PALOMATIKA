<?php

namespace App\Http\Controllers;

use App\Services\PdfTaskParser;
use Illuminate\Http\Request;

class TestPdfController extends Controller
{
    /**
     * Display parsed tasks from PDF for topic 06
     */
    public function topic06()
    {
        // Use manual data with all blocks from PDF
        $blocks = $this->getAllBlocksData();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic06', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data from PDF
     */
    protected function getAllBlocksData(): array
    {
        return [
            // =====================
            // БЛОК 1. ФИПИ
            // =====================
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    // Задание 1 - Простые дроби
                    [
                        'number' => 1,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{3}{4} \cdot \frac{6}{5}'],
                            ['id' => 7, 'expression' => '\frac{12}{5} : \frac{15}{2}'],
                            ['id' => 13, 'expression' => '\frac{1}{4} \cdot \frac{3}{25}'],
                            ['id' => 19, 'expression' => '\frac{14}{25} + \frac{3}{2}'],
                            ['id' => 2, 'expression' => '\frac{21}{5} \cdot \frac{3}{7}'],
                            ['id' => 8, 'expression' => '\frac{6}{5} : \frac{4}{11}'],
                            ['id' => 14, 'expression' => '\frac{1}{5} \cdot \frac{27}{50}'],
                            ['id' => 20, 'expression' => '\frac{9}{4} + \frac{8}{5}'],
                            ['id' => 3, 'expression' => '\frac{3}{5} \cdot \frac{25}{4}'],
                            ['id' => 9, 'expression' => '\frac{3}{5} : \frac{4}{35}'],
                            ['id' => 15, 'expression' => '\frac{1}{2} \cdot \frac{9}{25}'],
                            ['id' => 21, 'expression' => '\frac{11}{5} + \frac{13}{4}'],
                            ['id' => 4, 'expression' => '\frac{9}{5} \cdot \frac{2}{3}'],
                            ['id' => 10, 'expression' => '\frac{15}{4} : \frac{3}{7}'],
                            ['id' => 16, 'expression' => '\frac{1}{5} \cdot \frac{3}{4}'],
                            ['id' => 22, 'expression' => '\frac{1}{10} + \frac{21}{50}'],
                            ['id' => 5, 'expression' => '\frac{5}{3} \cdot \frac{9}{2}'],
                            ['id' => 11, 'expression' => '\frac{21}{2} : \frac{3}{5}'],
                            ['id' => 17, 'expression' => '\frac{1}{2} \cdot \frac{13}{50}'],
                            ['id' => 23, 'expression' => '\frac{3}{4} + \frac{7}{25}'],
                            ['id' => 6, 'expression' => '\frac{7}{5} \cdot \frac{12}{35}'],
                            ['id' => 12, 'expression' => '\frac{14}{5} : \frac{7}{2}'],
                            ['id' => 18, 'expression' => '\frac{1}{10} \cdot \frac{23}{20}'],
                            ['id' => 24, 'expression' => '\frac{4}{25} + \frac{15}{4}'],
                        ]
                    ],
                    // Задание 2 - Десятичные
                    [
                        'number' => 2,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '9{,}3 + 7{,}8'],
                            ['id' => 4, 'expression' => '5{,}7 - 7{,}6'],
                            ['id' => 7, 'expression' => '5{,}2 \cdot 3{,}1'],
                            ['id' => 10, 'expression' => '\frac{8{,}2}{4{,}1}'],
                            ['id' => 2, 'expression' => '8{,}7 + 4{,}6'],
                            ['id' => 5, 'expression' => '4{,}9 - 9{,}4'],
                            ['id' => 8, 'expression' => '2{,}1 \cdot 9{,}6'],
                            ['id' => 11, 'expression' => '\frac{13{,}2}{1{,}2}'],
                            ['id' => 3, 'expression' => '6{,}9 + 7{,}4'],
                            ['id' => 6, 'expression' => '6{,}1 - 2{,}5'],
                            ['id' => 9, 'expression' => '8{,}9 \cdot 4{,}3'],
                            ['id' => 12, 'expression' => '\frac{6{,}5}{1{,}3}'],
                        ]
                    ],
                    // Задание 3 - Приведение к знаменателю
                    [
                        'number' => 3,
                        'instruction' => 'Представьте выражение в виде дроби с указанным знаменателем',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{7}{9} - \frac{2}{5}', 'denominator' => 90],
                            ['id' => 2, 'expression' => '\frac{6}{7} - \frac{3}{5}', 'denominator' => 70],
                            ['id' => 3, 'expression' => '\frac{1}{7} + \frac{3}{4}', 'denominator' => 56],
                            ['id' => 4, 'expression' => '\frac{5}{8} + \frac{1}{3}', 'denominator' => 48],
                            ['id' => 5, 'expression' => '\frac{3}{4} - \frac{8}{11}', 'denominator' => 88],
                            ['id' => 6, 'expression' => '\frac{2}{3} - \frac{7}{13}', 'denominator' => 78],
                        ]
                    ],
                    // Задание 4 - Сложные дроби
                    [
                        'number' => 4,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{1}{\frac{1}{30} + \frac{1}{42}}'],
                            ['id' => 3, 'expression' => '\frac{1}{\frac{1}{36} + \frac{1}{45}}'],
                            ['id' => 5, 'expression' => '\frac{1}{\frac{1}{21} + \frac{1}{28}}'],
                            ['id' => 2, 'expression' => '\frac{1}{\frac{1}{36} - \frac{1}{44}}'],
                            ['id' => 4, 'expression' => '\frac{1}{\frac{1}{35} - \frac{1}{60}}'],
                            ['id' => 6, 'expression' => '\frac{1}{\frac{1}{72} - \frac{1}{99}}'],
                        ]
                    ]
                ]
            ],

            // =====================
            // БЛОК 2. ФИПИ. Расширенная версия
            // =====================
            [
                'number' => 2,
                'title' => 'ФИПИ. Расширенная версия',
                'zadaniya' => [
                    // Задание 1 - Сложные выражения с дробями
                    [
                        'number' => 1,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\left(\frac{17}{10} - \frac{1}{20}\right) \cdot \frac{2}{15}'],
                            ['id' => 4, 'expression' => '\left(\frac{10}{13} + \frac{15}{4}\right) \cdot \frac{26}{5}'],
                            ['id' => 7, 'expression' => '\left(\frac{3}{4} - \frac{1}{6}\right) \cdot 3'],
                            ['id' => 10, 'expression' => '\left(\frac{2}{20} + \frac{7}{30}\right) \cdot 15'],
                            ['id' => 2, 'expression' => '\left(\frac{5}{22} - \frac{8}{11}\right) \cdot \frac{11}{5}'],
                            ['id' => 5, 'expression' => '\left(\frac{17}{26} + \frac{11}{13}\right) \cdot \frac{17}{6}'],
                            ['id' => 8, 'expression' => '\left(\frac{2}{5} + \frac{13}{15}\right) \cdot 6'],
                            ['id' => 11, 'expression' => '\left(\frac{9}{10} - \frac{7}{15}\right) \cdot 3'],
                            ['id' => 3, 'expression' => '\left(\frac{5}{26} - \frac{3}{25}\right) \cdot \frac{13}{2}'],
                            ['id' => 6, 'expression' => '\left(\frac{11}{12} + \frac{11}{20}\right) \cdot \frac{15}{8}'],
                            ['id' => 9, 'expression' => '\left(\frac{3}{8} - \frac{1}{20}\right) \cdot 10'],
                            ['id' => 12, 'expression' => '\left(\frac{1}{6} + \frac{1}{4}\right) \cdot 9'],
                        ]
                    ],
                    // Задание 2 - Смешанные числа
                    [
                        'number' => 2,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\left(\frac{9}{16} + 2\frac{3}{8}\right) \cdot 4'],
                            ['id' => 5, 'expression' => '\left(1\frac{3}{4} + 2\frac{4}{5}\right) \cdot 30'],
                            ['id' => 9, 'expression' => '4\frac{7}{8} : \left(2\frac{3}{4} + 1\frac{10}{19}\right)'],
                            ['id' => 2, 'expression' => '\left(\frac{4}{9} - 3\frac{1}{15}\right) \cdot 9'],
                            ['id' => 6, 'expression' => '\left(\frac{1}{13} - 2\frac{3}{4}\right) \cdot 26'],
                            ['id' => 10, 'expression' => '1\frac{1}{12} : \left(1\frac{13}{18} - 2\frac{5}{9}\right)'],
                            ['id' => 3, 'expression' => '\left(2\frac{3}{4} + 2\frac{1}{5}\right) \cdot 16'],
                            ['id' => 7, 'expression' => '1\frac{8}{17} : \left(\frac{12}{17} + 2\frac{7}{11}\right)'],
                            ['id' => 11, 'expression' => '3\frac{1}{2} : \left(1\frac{4}{15} + 2\frac{9}{10}\right)'],
                            ['id' => 4, 'expression' => '\left(1\frac{11}{16} - 3\frac{7}{8}\right) \cdot 4'],
                            ['id' => 8, 'expression' => '3\frac{4}{9} : \left(1\frac{5}{9} - \frac{4}{7}\right)'],
                            ['id' => 12, 'expression' => '4\frac{1}{4} : \left(2\frac{7}{10} - 3\frac{1}{8}\right)'],
                        ]
                    ],
                    // Задание 3 - Степени дробей
                    [
                        'number' => 3,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '10 \cdot \left(\frac{1}{5}\right)^2 - 12 \cdot \frac{1}{5}'],
                            ['id' => 3, 'expression' => '21 \cdot \left(\frac{1}{7}\right)^2 - 10 \cdot \frac{1}{7}'],
                            ['id' => 5, 'expression' => '18 \cdot \left(\frac{1}{9}\right)^2 - 20 \cdot \frac{1}{9}'],
                            ['id' => 2, 'expression' => '8 \cdot \left(\frac{1}{4}\right)^2 - 14 \cdot \frac{1}{4}'],
                            ['id' => 4, 'expression' => '6 \cdot \left(\frac{1}{3}\right)^2 - 17 \cdot \frac{1}{3}'],
                            ['id' => 6, 'expression' => '15 \cdot \left(\frac{1}{5}\right)^2 - 8 \cdot \frac{1}{5}'],
                        ]
                    ],
                    // Задание 4 - Десятичные дроби сложные
                    [
                        'number' => 4,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{2{,}1}{6{,}6 - 2{,}4}'],
                            ['id' => 7, 'expression' => '\frac{9{,}5 + 8{,}9}{2{,}3}'],
                            ['id' => 13, 'expression' => '\frac{27}{3 \cdot 4{,}5}'],
                            ['id' => 19, 'expression' => '\frac{8{,}4 \cdot 1{,}3}{0{,}7}'],
                            ['id' => 2, 'expression' => '\frac{7{,}2}{8{,}3 - 8{,}6}'],
                            ['id' => 8, 'expression' => '\frac{6{,}8 - 4{,}7}{1{,}4}'],
                            ['id' => 14, 'expression' => '\frac{16}{3{,}2 \cdot 2}'],
                            ['id' => 20, 'expression' => '\frac{4{,}4 \cdot 0{,}3}{6{,}6}'],
                            ['id' => 3, 'expression' => '\frac{9{,}2}{0{,}5 - 2{,}8}'],
                            ['id' => 9, 'expression' => '\frac{7{,}5 + 3{,}5}{2{,}5}'],
                            ['id' => 15, 'expression' => '\frac{36}{4 \cdot 4{,}5}'],
                            ['id' => 21, 'expression' => '\frac{4{,}8 \cdot 0{,}4}{0{,}6}'],
                            ['id' => 4, 'expression' => '\frac{1{,}6}{2{,}5 + 0{,}7}'],
                            ['id' => 10, 'expression' => '\frac{6{,}9 - 4{,}1}{0{,}2}'],
                            ['id' => 16, 'expression' => '\frac{21}{17{,}5 \cdot 0{,}8}'],
                            ['id' => 22, 'expression' => '\frac{8{,}8 \cdot 0{,}8}{4{,}4}'],
                            ['id' => 5, 'expression' => '\frac{5{,}6}{1{,}9 + 2{,}1}'],
                            ['id' => 11, 'expression' => '\frac{1{,}7 + 3{,}8}{2{,}2}'],
                            ['id' => 17, 'expression' => '\frac{22}{4{,}4 \cdot 2{,}5}'],
                            ['id' => 23, 'expression' => '\frac{0{,}3 \cdot 7{,}5}{0{,}5}'],
                            ['id' => 6, 'expression' => '\frac{9{,}4}{4{,}1 + 5{,}3}'],
                            ['id' => 12, 'expression' => '\frac{7{,}2 - 6{,}1}{2{,}2}'],
                            ['id' => 18, 'expression' => '\frac{7}{12{,}5 \cdot 1{,}4}'],
                            ['id' => 24, 'expression' => '\frac{5{,}6 \cdot 0{,}3}{0{,}8}'],
                        ]
                    ],
                    // Задание 5 - Дроби с единицей
                    [
                        'number' => 5,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{0{,}9}{1 + \frac{1}{5}}'],
                            ['id' => 2, 'expression' => '\frac{2{,}6}{1 - \frac{1}{14}}'],
                            ['id' => 3, 'expression' => '\frac{1{,}3}{1 + \frac{1}{12}}'],
                            ['id' => 4, 'expression' => '\frac{1{,}2}{1 - \frac{1}{3}}'],
                            ['id' => 5, 'expression' => '\frac{0{,}6}{1 + \frac{1}{2}}'],
                            ['id' => 6, 'expression' => '\frac{0{,}8}{1 - \frac{1}{9}}'],
                        ]
                    ],
                    // Задание 6 - Выражения с отрицательными числами
                    [
                        'number' => 6,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '-7 \cdot (-4{,}7) - 6{,}8'],
                            ['id' => 7, 'expression' => '-0{,}8 \cdot (-10)^2 - 95'],
                            ['id' => 13, 'expression' => '30 - 0{,}8 \cdot (-10)^2'],
                            ['id' => 2, 'expression' => '-13 \cdot (-9{,}3) - 7{,}8'],
                            ['id' => 8, 'expression' => '0{,}7 \cdot (-10)^3 - 20'],
                            ['id' => 14, 'expression' => '80 + 0{,}4 \cdot (-10)^3'],
                            ['id' => 3, 'expression' => '-12 \cdot (-8{,}6) - 9{,}4'],
                            ['id' => 9, 'expression' => '-0{,}2 \cdot (-10)^2 + 55'],
                            ['id' => 15, 'expression' => '55 + 0{,}2 \cdot (-10)^2'],
                            ['id' => 4, 'expression' => '7{,}6 - 8 \cdot (-5{,}2)'],
                            ['id' => 10, 'expression' => '0{,}9 \cdot (-10)^3 + 50'],
                            ['id' => 16, 'expression' => '-60 + 0{,}4 \cdot (-10)^2'],
                            ['id' => 5, 'expression' => '6{,}8 - 11 \cdot (-6{,}1)'],
                            ['id' => 11, 'expression' => '-0{,}7 \cdot (-10)^2 - 120'],
                            ['id' => 17, 'expression' => '-80 + 0{,}3 \cdot (-10)^3'],
                            ['id' => 6, 'expression' => '5{,}3 - 9 \cdot (-4{,}4)'],
                            ['id' => 12, 'expression' => '0{,}6 \cdot (-10)^3 + 50'],
                            ['id' => 18, 'expression' => '-45 + 0{,}5 \cdot (-10)^2'],
                        ]
                    ],
                    // Задание 7 - Степени 10
                    [
                        'number' => 7,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(2{,}6 \cdot 10^{-2}) \cdot (9 \cdot 10^{-3})'],
                            ['id' => 7, 'expression' => '(7 \cdot 10^3)^2 \cdot (16 \cdot 10^{-4})'],
                            ['id' => 2, 'expression' => '(1{,}6 \cdot 10^{-5}) \cdot (6 \cdot 10^{-2})'],
                            ['id' => 8, 'expression' => '(2 \cdot 10^2)^4 \cdot (19 \cdot 10^{-6})'],
                            ['id' => 3, 'expression' => '(1{,}7 \cdot 10^{-3}) \cdot (5 \cdot 10^{-4})'],
                            ['id' => 9, 'expression' => '(8 \cdot 10^2)^2 \cdot (3 \cdot 10^{-2})'],
                            ['id' => 4, 'expression' => '(2{,}1 \cdot 10^{-2}) \cdot (2 \cdot 10^{-2})'],
                            ['id' => 10, 'expression' => '(9 \cdot 10^{-2})^2 \cdot (11 \cdot 10^5)'],
                            ['id' => 5, 'expression' => '(2{,}2 \cdot 10^{-2}) \cdot (3 \cdot 10^{-4})'],
                            ['id' => 11, 'expression' => '(16 \cdot 10^{-2})^2 \cdot (13 \cdot 10^4)'],
                            ['id' => 6, 'expression' => '(1{,}2 \cdot 10^{-3}) \cdot (7 \cdot 10^{-2})'],
                            ['id' => 12, 'expression' => '(14 \cdot 10^{-2})^2 \cdot (12 \cdot 10^3)'],
                        ]
                    ],
                    // Задание 8 - Комбинированные степени
                    [
                        'number' => 8,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '0{,}7 \cdot (-10)^3 - 4 \cdot (-10)^2 - 63'],
                            ['id' => 4, 'expression' => '-0{,}7 \cdot (-10)^4 - 8 \cdot (-10)^2 - 26'],
                            ['id' => 2, 'expression' => '-0{,}4 \cdot (-10)^4 + 3 \cdot (-10)^2 - 98'],
                            ['id' => 5, 'expression' => '0{,}4 \cdot (-10)^3 + 7 \cdot (-10)^2 + 64'],
                            ['id' => 3, 'expression' => '0{,}8 \cdot (-10)^4 + 3 \cdot (-10)^3 + 78'],
                            ['id' => 6, 'expression' => '-0{,}3 \cdot (-10)^4 + 4 \cdot (-10)^2 - 59'],
                        ]
                    ],
                    // Задание 9 - Произведение десятичных
                    [
                        'number' => 9,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '0{,}0006 \cdot 6 \cdot 600000'],
                            ['id' => 4, 'expression' => '0{,}005 \cdot 0{,}5 \cdot 50'],
                            ['id' => 2, 'expression' => '0{,}007 \cdot 0{,}7 \cdot 70'],
                            ['id' => 5, 'expression' => '0{,}003 \cdot 0{,}0003 \cdot 300'],
                            ['id' => 3, 'expression' => '0{,}0008 \cdot 0{,}008 \cdot 80000'],
                            ['id' => 6, 'expression' => '0{,}004 \cdot 0{,}04 \cdot 40000'],
                        ]
                    ],
                    // Задание 10 - Степени с основаниями
                    [
                        'number' => 10,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '-0{,}2 \cdot (-7)^4 - 1 \cdot (-7)^3 - 13'],
                            ['id' => 4, 'expression' => '0{,}5 \cdot (-6)^4 + 2 \cdot (-6)^2 - 30'],
                            ['id' => 2, 'expression' => '-0{,}9 \cdot (-2)^3 + 2{,}9 \cdot (-2)^2 - 22'],
                            ['id' => 5, 'expression' => '-1{,}1 \cdot (-3)^4 - 0{,}9 \cdot (-3)^3 - 15'],
                            ['id' => 3, 'expression' => '0{,}1 \cdot (-8)^3 + 0{,}2 \cdot (-8)^2 - 25'],
                            ['id' => 6, 'expression' => '0{,}2 \cdot (-4)^3 + 3 \cdot (-4)^2 - 17'],
                        ]
                    ],
                    // Задание 11 - Десятичные суммы
                    [
                        'number' => 11,
                        'instruction' => 'Запишите десятичную дробь, равную сумме',
                        'tasks' => [
                            ['id' => 1, 'expression' => '1 \cdot 10^{-1} + 7 \cdot 10^{-3} + 2 \cdot 10^{-4}'],
                            ['id' => 4, 'expression' => '8 \cdot 10^0 + 9 \cdot 10^{-2} + 3 \cdot 10^{-4}'],
                            ['id' => 2, 'expression' => '9 \cdot 10^1 + 3 \cdot 10^{-3} + 8 \cdot 10^{-4}'],
                            ['id' => 5, 'expression' => '6 \cdot 10^1 + 7 \cdot 10^{-2} + 5 \cdot 10^{-3}'],
                            ['id' => 3, 'expression' => '2 \cdot 10^0 + 6 \cdot 10^{-1} + 4 \cdot 10^{-3}'],
                            ['id' => 6, 'expression' => '5 \cdot 10^{-1} + 6 \cdot 10^{-2} + 4 \cdot 10^{-4}'],
                        ]
                    ],
                ]
            ],

            // =====================
            // БЛОК 3. Типовые экзаменационные варианты
            // =====================
            [
                'number' => 3,
                'title' => 'Типовые экзаменационные варианты',
                'zadaniya' => [
                    // Задание 1
                    [
                        'number' => 1,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\left(\frac{1}{17} + 1\frac{1}{4}\right) : \frac{1}{34}'],
                            ['id' => 3, 'expression' => '\left(\frac{7}{9} + 1\frac{4}{5}\right) : \frac{1}{18}'],
                            ['id' => 5, 'expression' => '\left(\frac{13}{24} + 1\frac{1}{15}\right) : \frac{1}{24}'],
                            ['id' => 2, 'expression' => '\left(\frac{3}{4} - 2\frac{9}{10}\right) : \frac{1}{12}'],
                            ['id' => 4, 'expression' => '\left(\frac{4}{11} - 2\frac{1}{4}\right) : \frac{1}{22}'],
                            ['id' => 6, 'expression' => '\left(\frac{15}{26} - 2\frac{3}{4}\right) : \frac{1}{26}'],
                        ]
                    ],
                    // Задание 2
                    [
                        'number' => 2,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{\frac{1}{20} + \frac{1}{12}}{\frac{1}{27}}'],
                            ['id' => 3, 'expression' => '\frac{\frac{1}{18} + \frac{1}{45}}{\frac{5}{27}}'],
                            ['id' => 5, 'expression' => '\frac{\frac{1}{28} + \frac{1}{42}}{\frac{1}{21}}'],
                            ['id' => 2, 'expression' => '\frac{\frac{1}{12} - \frac{1}{21}}{\frac{1}{70}}'],
                            ['id' => 4, 'expression' => '\frac{\frac{1}{72} - \frac{1}{88}}{\frac{5}{99}}'],
                            ['id' => 6, 'expression' => '\frac{\frac{1}{40} - \frac{1}{65}}{\frac{1}{78}}'],
                        ]
                    ],
                    // Задание 3
                    [
                        'number' => 3,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '1{,}9 - 3{,}5 \cdot 7{,}2'],
                            ['id' => 3, 'expression' => '5{,}1 + 2{,}8 \cdot 2{,}5'],
                            ['id' => 2, 'expression' => '-9{,}2 - 0{,}4 \cdot 6{,}5'],
                            ['id' => 4, 'expression' => '-3{,}6 + 7{,}2 \cdot 1{,}5'],
                        ]
                    ],
                    // Задание 4
                    [
                        'number' => 4,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{3}{16} : \left(-\frac{5}{56}\right) + 3{,}8'],
                            ['id' => 3, 'expression' => '-\frac{14}{23} : \frac{35}{46} + 2{,}9'],
                            ['id' => 2, 'expression' => '\frac{7}{18} : \left(-\frac{10}{27}\right) - 2{,}4'],
                            ['id' => 4, 'expression' => '-\frac{15}{58} : \frac{3}{29} - 5{,}63'],
                        ]
                    ],
                    // Задание 5 - Несократимые дроби
                    [
                        'number' => 5,
                        'instruction' => 'Найдите значение выражения. Представьте результат в виде несократимой обыкновенной дроби. В ответ запишите числитель этой дроби.',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{2}{5} + \frac{3}{11}'],
                            ['id' => 4, 'expression' => '\frac{1}{45} + \frac{5}{9}'],
                            ['id' => 7, 'expression' => '3\frac{1}{12} - 2\frac{1}{4}'],
                            ['id' => 10, 'expression' => '9\frac{2}{15} - 8\frac{32}{33}'],
                            ['id' => 2, 'expression' => '\frac{7}{13} + \frac{1}{3}'],
                            ['id' => 5, 'expression' => '\frac{3}{20} + \frac{7}{36}'],
                            ['id' => 8, 'expression' => '5\frac{1}{15} - 4\frac{2}{5}'],
                            ['id' => 11, 'expression' => '2\frac{3}{28} - 1\frac{17}{36}'],
                            ['id' => 3, 'expression' => '\frac{5}{7} + \frac{4}{21}'],
                            ['id' => 6, 'expression' => '\frac{2}{45} + \frac{9}{35}'],
                            ['id' => 9, 'expression' => '7\frac{1}{18} - 6\frac{13}{14}'],
                            ['id' => 12, 'expression' => '6\frac{2}{21} - 5\frac{31}{33}'],
                        ]
                    ],
                    // Задание 6 - Ещё несократимые дроби
                    [
                        'number' => 6,
                        'instruction' => 'Найдите значение выражения. Представьте результат в виде несократимой обыкновенной дроби. В ответ запишите числитель этой дроби.',
                        'tasks' => [
                            ['id' => 1, 'expression' => '1\frac{19}{29} \cdot \frac{7}{48}'],
                            ['id' => 5, 'expression' => '\frac{7}{12} : 2\frac{1}{4}'],
                            ['id' => 9, 'expression' => '\frac{1}{15} + 4\frac{4}{5} \cdot \frac{2}{21}'],
                            ['id' => 13, 'expression' => '4\frac{25}{27} - \frac{3}{38} \cdot \frac{5}{22}'],
                            ['id' => 2, 'expression' => '1\frac{13}{58} \cdot \frac{9}{71}'],
                            ['id' => 6, 'expression' => '\frac{9}{14} : 1\frac{4}{7}'],
                            ['id' => 10, 'expression' => '\frac{3}{20} + 3\frac{3}{4} \cdot \frac{1}{27}'],
                            ['id' => 14, 'expression' => '6\frac{24}{35} - \frac{1}{9} \cdot \frac{4}{15}'],
                            ['id' => 3, 'expression' => '1\frac{15}{34} \cdot \frac{17}{49}'],
                            ['id' => 7, 'expression' => '\frac{8}{11} : 2\frac{2}{5}'],
                            ['id' => 11, 'expression' => '\frac{1}{14} + 2\frac{1}{12} \cdot \frac{2}{15}'],
                            ['id' => 15, 'expression' => '2\frac{39}{40} - \frac{2}{7} \cdot \frac{3}{28}'],
                            ['id' => 4, 'expression' => '1\frac{11}{45} \cdot \frac{25}{56}'],
                            ['id' => 8, 'expression' => '\frac{6}{13} : 1\frac{1}{8}'],
                            ['id' => 12, 'expression' => '\frac{10}{21} + 2\frac{2}{15} \cdot \frac{3}{14}'],
                            ['id' => 16, 'expression' => '5\frac{25}{28} - \frac{4}{45} \cdot \frac{5}{39}'],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Display parsed tasks from PDF for topic 07
     */
    public function topic07()
    {
        $blocks = $this->getAllBlocksData07();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic07', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data for Topic 07 - Числа, координатная прямая
     */
    protected function getAllBlocksData07(): array
    {
        return [
            // =====================
            // БЛОК 1. ФИПИ
            // =====================
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    // Задание 1 - Утверждения для числа a
                    [
                        'number' => 1,
                        'instruction' => 'На координатной прямой отмечено число a. Какое из утверждений для этого числа является верным?',
                        'type' => 'choice',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-000.png', 'options' => ['$a - 6 < 0$', '$6 - a > 0$', '$a - 7 > 0$', '$8 - a < 0$']],
                            ['id' => 2, 'image' => 'img-001.png', 'options' => ['$5 - a < 0$', '$a - 6 > 0$', '$a - 5 < 0$', '$4 - a > 0$']],
                            ['id' => 3, 'image' => 'img-002.png', 'options' => ['$a - 4 < 0$', '$a - 6 > 0$', '$6 - a > 0$', '$7 - a < 0$']],
                            ['id' => 4, 'image' => 'img-003.png', 'options' => ['$8 - a > 0$', '$8 - a < 0$', '$a - 7 < 0$', '$a - 9 > 0$']],
                            ['id' => 5, 'image' => 'img-004.png', 'options' => ['$4 - a > 0$', '$a - 7 < 0$', '$a - 8 > 0$', '$8 - a < 0$']],
                            ['id' => 6, 'image' => 'img-005.png', 'options' => ['$4 - a > 0$', '$a - 4 < 0$', '$a - 3 < 0$', '$6 - a > 0$']],
                        ]
                    ],
                    // Задание 2 - Два числа x и y на прямой
                    [
                        'number' => 2,
                        'instruction' => 'На координатной прямой отмечены числа. Какое из приведённых утверждений для этих чисел верно?',
                        'type' => 'choice',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-006.png', 'options' => ['$x + y < 0$', '$xy < 0$', '$y - x > 0$', '$x^2 y > 0$']],
                            ['id' => 2, 'image' => 'img-007.png', 'options' => ['$a + b > 0$', '$a^2 b < 0$', '$ab > 0$', '$a - b < 0$']],
                            ['id' => 3, 'image' => 'img-008.png', 'options' => ['$xy > 0$', '$x^2 y < 0$', '$x + y > 0$', '$x - y < 0$']],
                            ['id' => 4, 'image' => 'img-009.png', 'options' => ['$a + b < 0$', '$a - b > 0$', '$ab^2 > 0$', '$ab < 0$']],
                            ['id' => 5, 'image' => 'img-010.png', 'options' => ['$xy^2 > 0$', '$x - y < 0$', '$x + y > 0$', '$xy > 0$']],
                            ['id' => 6, 'image' => 'img-011.png', 'options' => ['$ab^2 > 0$', '$a - b < 0$', '$ab > 0$', '$a + b > 0$']],
                        ]
                    ],
                    // Задание 3 - Разности q-p, q-r, r-p положительна
                    [
                        'number' => 3,
                        'instruction' => 'На координатной прямой отмечены числа p, q и r. Какая из разностей q − p, q − r, r − p положительна?',
                        'type' => 'simple_choice',
                        'image' => 'img-012.png',
                        'options' => ['$q - p$', '$q - r$', '$r - p$', 'невозможно определить'],
                    ],
                    // Задание 4 - Разности z-x, y-z, x-y отрицательна
                    [
                        'number' => 4,
                        'instruction' => 'На координатной прямой отмечены числа x, y и z. Какая из разностей z − x, y − z, x − y отрицательна?',
                        'type' => 'simple_choice',
                        'image' => 'img-013.png',
                        'options' => ['$z - x$', '$y - z$', '$x - y$', 'невозможно определить'],
                    ],
                    // Задание 5 - Разности a-b, a-c, c-b положительна
                    [
                        'number' => 5,
                        'instruction' => 'На координатной прямой отмечены числа a, b и c. Какая из разностей a − b, a − c, c − b положительна?',
                        'type' => 'simple_choice',
                        'image' => 'img-014.png',
                        'options' => ['$a - b$', '$a - c$', '$c - b$', 'невозможно определить'],
                    ],
                    // Задание 6 - Разности q-p, q-r, r-p отрицательна
                    [
                        'number' => 6,
                        'instruction' => 'На координатной прямой отмечены числа p, q и r. Какая из разностей q − p, q − r, r − p отрицательна?',
                        'type' => 'simple_choice',
                        'image' => 'img-015.png',
                        'options' => ['$q - p$', '$q - r$', '$r - p$', 'невозможно определить'],
                    ],
                    // Задание 7 - Разности z-x, y-z, x-y положительна
                    [
                        'number' => 7,
                        'instruction' => 'На координатной прямой отмечены числа x, y и z. Какая из разностей z − x, y − z, x − y положительна?',
                        'type' => 'simple_choice',
                        'image' => 'img-016.png',
                        'options' => ['$z - x$', '$y - z$', '$x - y$', 'невозможно определить'],
                    ],
                    // Задание 8 - Разности a-b, a-c, c-b отрицательна
                    [
                        'number' => 8,
                        'instruction' => 'На координатной прямой отмечены числа a, b и c. Какая из разностей a − b, a − c, c − b отрицательна?',
                        'type' => 'simple_choice',
                        'image' => 'img-017.png',
                        'options' => ['$a - b$', '$a - c$', '$c - b$', 'невозможно определить'],
                    ],
                    // Задание 9 - Точка соответствует числу
                    [
                        'number' => 9,
                        'instruction' => 'На координатной прямой отмечены точки A, B, C и D. Одна из них соответствует данному числу. Какая это точка?',
                        'type' => 'fraction_choice',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-018.png', 'expression' => '\frac{63}{11}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 2, 'image' => 'img-019.png', 'expression' => '\frac{116}{15}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 3, 'image' => 'img-020.png', 'expression' => '\frac{107}{13}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 4, 'image' => 'img-021.png', 'expression' => '\frac{100}{19}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 5, 'image' => 'img-022.png', 'expression' => '\frac{132}{17}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 6, 'image' => 'img-023.png', 'expression' => '\frac{92}{9}', 'options' => ['A', 'B', 'C', 'D']],
                        ]
                    ],
                    // Задание 10 - Между какими целыми числами
                    [
                        'number' => 10,
                        'instruction' => 'Между какими целыми числами заключено число...',
                        'type' => 'interval_choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{130}{11}', 'options' => ['10 и 11', '11 и 12', '12 и 13', '13 и 14']],
                            ['id' => 2, 'expression' => '\frac{124}{15}', 'options' => ['8 и 9', '9 и 10', '10 и 11', '11 и 12']],
                            ['id' => 3, 'expression' => '\frac{230}{19}', 'options' => ['11 и 12', '12 и 13', '13 и 14', '14 и 15']],
                            ['id' => 4, 'expression' => '\frac{140}{17}', 'options' => ['5 и 6', '6 и 7', '7 и 8', '8 и 9']],
                            ['id' => 5, 'expression' => '\frac{110}{13}', 'options' => ['8 и 9', '9 и 10', '10 и 11', '11 и 12']],
                            ['id' => 6, 'expression' => '\frac{131}{12}', 'options' => ['10 и 11', '11 и 12', '12 и 13', '13 и 14']],
                        ]
                    ],
                    // Задание 11 - Промежуток принадлежности дроби
                    [
                        'number' => 11,
                        'instruction' => 'Какому из данных промежутков принадлежит число...',
                        'type' => 'interval_choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{2}{9}', 'options' => ['[0,1; 0,2]', '[0,2; 0,3]', '[0,3; 0,4]', '[0,4; 0,5]']],
                            ['id' => 2, 'expression' => '\frac{7}{11}', 'options' => ['[0,4; 0,5]', '[0,5; 0,6]', '[0,6; 0,7]', '[0,7; 0,8]']],
                            ['id' => 3, 'expression' => '\frac{5}{13}', 'options' => ['[0,2; 0,3]', '[0,3; 0,4]', '[0,4; 0,5]', '[0,5; 0,6]']],
                            ['id' => 4, 'expression' => '\frac{3}{7}', 'options' => ['[0,1; 0,2]', '[0,2; 0,3]', '[0,3; 0,4]', '[0,4; 0,5]']],
                            ['id' => 5, 'expression' => '\frac{5}{11}', 'options' => ['[0,2; 0,3]', '[0,3; 0,4]', '[0,4; 0,5]', '[0,5; 0,6]']],
                            ['id' => 6, 'expression' => '\frac{9}{13}', 'options' => ['[0,5; 0,6]', '[0,6; 0,7]', '[0,7; 0,8]', '[0,8; 0,9]']],
                        ]
                    ],
                    // Задание 12 - Число между двумя дробями
                    [
                        'number' => 12,
                        'instruction' => 'Какое из следующих чисел заключено между числами...',
                        'type' => 'between_fractions',
                        'tasks' => [
                            ['id' => 1, 'left' => '\frac{8}{3}', 'right' => '\frac{11}{4}', 'options' => ['2,7', '2,8', '2,9', '3']],
                            ['id' => 2, 'left' => '\frac{8}{13}', 'right' => '\frac{12}{17}', 'options' => ['0,6', '0,7', '0,8', '0,9']],
                            ['id' => 3, 'left' => '\frac{15}{11}', 'right' => '\frac{13}{9}', 'options' => ['1,4', '1,5', '1,6', '1,7']],
                            ['id' => 4, 'left' => '\frac{17}{15}', 'right' => '\frac{16}{13}', 'options' => ['1,2', '1,3', '1,4', '1,5']],
                            ['id' => 5, 'left' => '\frac{19}{8}', 'right' => '\frac{17}{7}', 'options' => ['2,3', '2,4', '2,5', '2,6']],
                            ['id' => 6, 'left' => '\frac{18}{17}', 'right' => '\frac{17}{15}', 'options' => ['1,0', '1,1', '1,2', '1,3']],
                        ]
                    ],
                    // Задание 13 - Принадлежность отрезку
                    [
                        'number' => 13,
                        'instruction' => 'Какое из данных чисел принадлежит отрезку...',
                        'type' => 'segment_choice',
                        'tasks' => [
                            ['id' => 1, 'segment' => '[3; 4]', 'options' => ['\frac{47}{14}', '\frac{57}{14}', '\frac{61}{14}', '\frac{65}{14}']],
                            ['id' => 2, 'segment' => '[4; 5]', 'options' => ['\frac{58}{17}', '\frac{72}{17}', '\frac{87}{17}', '\frac{91}{17}']],
                            ['id' => 3, 'segment' => '[7; 8]', 'options' => ['\frac{57}{9}', '\frac{62}{9}', '\frac{70}{9}', '\frac{79}{9}']],
                            ['id' => 4, 'segment' => '[6; 7]', 'options' => ['\frac{67}{12}', '\frac{71}{12}', '\frac{83}{12}', '\frac{91}{12}']],
                            ['id' => 5, 'segment' => '[5; 6]', 'options' => ['\frac{68}{13}', '\frac{79}{13}', '\frac{82}{13}', '\frac{89}{13}']],
                            ['id' => 6, 'segment' => '[4; 5]', 'options' => ['\frac{49}{15}', '\frac{52}{15}', '\frac{58}{15}', '\frac{71}{15}']],
                        ]
                    ],
                    // Задание 14 - Точка A соответствует числу (дроби)
                    [
                        'number' => 14,
                        'instruction' => 'Одно из чисел отмечено на прямой точкой А. Какое это число?',
                        'type' => 'fraction_options',
                        'tasks' => [
                            ['id' => 1, 'options' => ['\frac{3}{11}', '\frac{7}{11}', '\frac{8}{11}', '\frac{13}{11}']],
                            ['id' => 2, 'options' => ['\frac{10}{17}', '\frac{11}{17}', '\frac{13}{17}', '\frac{14}{17}']],
                            ['id' => 3, 'options' => ['\frac{3}{13}', '\frac{9}{13}', '\frac{10}{13}', '\frac{12}{13}']],
                            ['id' => 4, 'options' => ['\frac{10}{23}', '\frac{11}{23}', '\frac{13}{23}', '\frac{14}{23}']],
                            ['id' => 5, 'options' => ['\frac{2}{7}', '\frac{4}{7}', '\frac{10}{7}', '\frac{11}{7}']],
                            ['id' => 6, 'options' => ['\frac{6}{23}', '\frac{7}{23}', '\frac{11}{23}', '\frac{12}{23}']],
                        ]
                    ],
                    // Задание 15 - Точка A соответствует числу (большие дроби)
                    [
                        'number' => 15,
                        'instruction' => 'Одно из чисел отмечено на прямой точкой A. Какое это число?',
                        'type' => 'fraction_options',
                        'tasks' => [
                            ['id' => 1, 'options' => ['\frac{55}{19}', '\frac{64}{19}', '\frac{72}{19}', '\frac{79}{19}']],
                            ['id' => 2, 'options' => ['\frac{71}{15}', '\frac{79}{15}', '\frac{86}{15}', '\frac{92}{15}']],
                            ['id' => 3, 'options' => ['\frac{73}{22}', '\frac{83}{22}', '\frac{93}{22}', '\frac{113}{22}']],
                            ['id' => 4, 'options' => ['\frac{58}{13}', '\frac{69}{13}', '\frac{76}{13}', '\frac{83}{13}']],
                            ['id' => 5, 'options' => ['\frac{75}{23}', '\frac{85}{23}', '\frac{97}{23}', '\frac{110}{23}']],
                            ['id' => 6, 'options' => ['\frac{31}{11}', '\frac{37}{11}', '\frac{41}{11}', '\frac{47}{11}']],
                        ]
                    ],
                    // Задание 16 - Десятичные числа на прямой
                    [
                        'number' => 16,
                        'instruction' => 'На координатной прямой точки A, B, C и D соответствуют числам. Какой точке соответствует указанное число?',
                        'type' => 'decimal_choice',
                        'tasks' => [
                            ['id' => 1, 'numbers' => '0,0137; 0,103; 0,03; 0,021', 'target' => '0,03', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 2, 'numbers' => '−0,502; 0,25; 0,205; 0,52', 'target' => '0,205', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 3, 'numbers' => '0,508; 0,85; −0,05; 0,058', 'target' => '0,058', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 4, 'numbers' => '−0,39; −0,09; −0,93; 0,03', 'target' => '−0,09', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 5, 'numbers' => '0,271; −0,112; 0,041; −0,267', 'target' => '0,271', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 6, 'numbers' => '−0,201; −0,012; −0,304; 0,021', 'target' => '−0,304', 'options' => ['A', 'B', 'C', 'D']],
                        ]
                    ],
                    // Задание 17 - Корни на прямой
                    [
                        'number' => 17,
                        'instruction' => 'На координатной прямой отмечены точки A, B, C, D. Одна из них соответствует данному числу. Какая это точка?',
                        'type' => 'sqrt_choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{86}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 2, 'expression' => '\sqrt{46}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 3, 'expression' => '\sqrt{68}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 4, 'expression' => '\sqrt{85}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 5, 'expression' => '\sqrt{39}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 6, 'expression' => '\sqrt{76}', 'options' => ['A', 'B', 'C', 'D']],
                        ]
                    ],
                    // Задание 18 - Корень между целыми
                    [
                        'number' => 18,
                        'instruction' => 'Между какими целыми числами заключено число...',
                        'type' => 'sqrt_interval',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{89}', 'options' => ['4 и 5', '29 и 31', '9 и 10', '88 и 90']],
                            ['id' => 2, 'expression' => '\sqrt{27}', 'options' => ['2 и 3', '5 и 6', '12 и 14', '26 и 28']],
                            ['id' => 3, 'expression' => '\sqrt{58}', 'options' => ['19 и 21', '57 и 59', '3 и 4', '7 и 8']],
                            ['id' => 4, 'expression' => '\sqrt{73}', 'options' => ['8 и 9', '72 и 74', '24 и 26', '4 и 5']],
                            ['id' => 5, 'expression' => '\sqrt{30}', 'options' => ['11 и 13', '5 и 6', '2 и 3', '29 и 31']],
                            ['id' => 6, 'expression' => '\sqrt{56}', 'options' => ['55 и 57', '3 и 4', '19 и 21', '7 и 8']],
                        ]
                    ],
                    // Задание 19 - Корень принадлежит промежутку
                    [
                        'number' => 19,
                        'instruction' => 'Какое из данных чисел принадлежит промежутку...',
                        'type' => 'sqrt_segment',
                        'tasks' => [
                            ['id' => 1, 'segment' => '[5; 6]', 'options' => ['\sqrt{5}', '\sqrt{6}', '\sqrt{24}', '\sqrt{32}']],
                            ['id' => 2, 'segment' => '[6; 7]', 'options' => ['\sqrt{6}', '\sqrt{7}', '\sqrt{38}', '\sqrt{50}']],
                            ['id' => 3, 'segment' => '[7; 8]', 'options' => ['\sqrt{7}', '\sqrt{8}', '\sqrt{62}', '\sqrt{72}']],
                            ['id' => 4, 'segment' => '[6; 7]', 'options' => ['\sqrt{6}', '\sqrt{7}', '\sqrt{40}', '\sqrt{51}']],
                            ['id' => 5, 'segment' => '[5; 6]', 'options' => ['\sqrt{5}', '\sqrt{6}', '\sqrt{28}', '\sqrt{41}']],
                        ]
                    ],
                ]
            ],

            // =====================
            // БЛОК 2. ФИПИ. Расширенная версия
            // =====================
            [
                'number' => 2,
                'title' => 'ФИПИ. Расширенная версия',
                'zadaniya' => [
                    // Задание 1 - Сравнение x и y
                    [
                        'number' => 1,
                        'instruction' => 'На координатной прямой отмечены числа. Какое из следующих утверждений верно?',
                        'type' => 'comparison',
                        'tasks' => [
                            ['id' => 1, 'options' => ['$x < y$ и $|x| < |y|$', '$x < y$ и $|x| > |y|$', '$x > y$ и $|x| > |y|$', '$x > y$ и $|x| < |y|$']],
                            ['id' => 2, 'options' => ['$a < b$ и $|a| < |b|$', '$a < b$ и $|a| > |b|$', '$a > b$ и $|a| > |b|$', '$a > b$ и $|a| < |b|$']],
                        ]
                    ],
                    // Задание 2 - Наименьшее среди степеней
                    [
                        'number' => 2,
                        'instruction' => 'На координатной прямой отмечены числа. Какое из перечисленных чисел наименьшее?',
                        'type' => 'power_choice',
                        'tasks' => [
                            ['id' => 1, 'options' => ['$a$', '$a^2$', '$a^3$', 'нет данных']],
                            ['id' => 2, 'options' => ['$a^2$', '$a^3$', '$a^4$', 'нет данных']],
                            ['id' => 3, 'options' => ['$a^2$', '$a^3$', '$a^4$', 'нет данных']],
                            ['id' => 4, 'options' => ['$a$', '$a^2$', '$a^3$', 'нет данных']],
                        ]
                    ],
                    // Задание 3 - Сравнение дробей
                    [
                        'number' => 3,
                        'instruction' => 'Сравните числа, если a, b — положительные числа и...',
                        'type' => 'compare_fractions',
                        'tasks' => [
                            ['id' => 1, 'condition' => '$a < b$', 'question' => '\frac{2}{a} \text{ и } \frac{2}{b}', 'options' => ['$\frac{2}{a} > \frac{2}{b}$', '$\frac{2}{a} < \frac{2}{b}$', '$\frac{2}{a} = \frac{2}{b}$', 'невозможно']],
                            ['id' => 2, 'condition' => '$a > b$', 'question' => '\frac{1}{a} \text{ и } \frac{1}{b}', 'options' => ['$\frac{1}{a} > \frac{1}{b}$', '$\frac{1}{a} < \frac{1}{b}$', '$\frac{1}{a} = \frac{1}{b}$', 'невозможно']],
                        ]
                    ],
                    // Задание 4 - Неверные утверждения
                    [
                        'number' => 4,
                        'instruction' => 'Какие из данных утверждений неверны, если a < c?',
                        'type' => 'false_statements',
                        'tasks' => [
                            ['id' => 1, 'options' => ['$a - 49 < c - 49$', '$a + 23 < c + 23$', '$-\frac{a}{26} < -\frac{c}{26}$', '$\frac{a}{5} < \frac{c}{5}$']],
                            ['id' => 2, 'options' => ['$a - 24 < c - 24$', '$a + 33 < c + 33$', '$-\frac{a}{5} < -\frac{c}{5}$', '$\frac{a}{17} < \frac{c}{17}$']],
                        ]
                    ],
                    // Задание 5 - Расположите в порядке возрастания
                    [
                        'number' => 5,
                        'instruction' => 'Расположите в порядке возрастания числа.',
                        'type' => 'ordering',
                        'tasks' => [
                            ['id' => 1, 'options' => ['$\frac{1}{a}, 1, \frac{1}{b}$', '$1, \frac{1}{b}, \frac{1}{a}$', '$\frac{1}{a}, \frac{1}{b}, 1$', '$\frac{1}{b}, \frac{1}{a}, 1$']],
                            ['id' => 2, 'options' => ['$\frac{1}{b}, 1, \frac{1}{a}$', '$\frac{1}{a}, 1, \frac{1}{b}$', '$\frac{1}{a}, \frac{1}{b}, 1$', '$\frac{1}{b}, \frac{1}{a}, 1$']],
                            ['id' => 3, 'options' => ['$1, \frac{1}{a}, \frac{1}{c}$', '$\frac{1}{c}, \frac{1}{a}, 1$', '$\frac{1}{a}, \frac{1}{c}, 1$', '$1, \frac{1}{c}, \frac{1}{a}$']],
                            ['id' => 4, 'options' => ['$\frac{1}{x}, 1, \frac{1}{y}$', '$\frac{1}{y}, 1, \frac{1}{x}$', '$\frac{1}{x}, \frac{1}{y}, 1$', '$1, \frac{1}{x}, \frac{1}{y}$']],
                        ]
                    ],
                    // Задание 6 - Какому числу соответствует точка
                    [
                        'number' => 6,
                        'instruction' => 'На координатной прямой точками отмечены числа. Какому числу соответствует точка?',
                        'type' => 'point_value',
                        'tasks' => [
                            ['id' => 1, 'point' => 'C', 'options' => ['\frac{4}{7}', '\frac{11}{5}', '2,6', '0,3']],
                            ['id' => 2, 'point' => 'D', 'options' => ['\frac{11}{7}', '\frac{3}{2}', '1,55', '1,7']],
                            ['id' => 3, 'point' => 'C', 'options' => ['\frac{8}{3}', '\frac{9}{4}', '2,55', '2,4']],
                            ['id' => 4, 'point' => 'D', 'options' => ['\frac{4}{13}', '\frac{5}{14}', '0,29', '0,3']],
                        ]
                    ],
                    // Задание 7 - Какая точка соответствует числу (простые дроби)
                    [
                        'number' => 7,
                        'instruction' => 'Одна из точек, отмеченных на координатной прямой, соответствует данному числу. Какая это точка?',
                        'type' => 'fraction_point',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{1}{7}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 2, 'expression' => '\frac{8}{11}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 3, 'expression' => '\frac{2}{9}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 4, 'expression' => '\frac{10}{13}', 'options' => ['A', 'B', 'C', 'D']],
                        ]
                    ],
                    // Задание 8 - Промежуток для корня
                    [
                        'number' => 8,
                        'instruction' => 'Какому из данных промежутков принадлежит число...',
                        'type' => 'sqrt_interval',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{58}', 'options' => ['[4; 5]', '[5; 6]', '[6; 7]', '[7; 8]']],
                            ['id' => 2, 'expression' => '\sqrt{27}', 'options' => ['[4; 5]', '[5; 6]', '[6; 7]', '[7; 8]']],
                            ['id' => 3, 'expression' => '\sqrt{19}', 'options' => ['[4; 5]', '[5; 6]', '[6; 7]', '[7; 8]']],
                            ['id' => 4, 'expression' => '\sqrt{63}', 'options' => ['[4; 5]', '[5; 6]', '[6; 7]', '[7; 8]']],
                            ['id' => 5, 'expression' => '\sqrt{42}', 'options' => ['[4; 5]', '[5; 6]', '[6; 7]', '[7; 8]']],
                            ['id' => 6, 'expression' => '\sqrt{31}', 'options' => ['[4; 5]', '[5; 6]', '[6; 7]', '[7; 8]']],
                        ]
                    ],
                    // Задание 9 - Корень отмечен точкой A
                    [
                        'number' => 9,
                        'instruction' => 'Одно из чисел отмечено на прямой точкой A. Какое это число?',
                        'type' => 'sqrt_options',
                        'tasks' => [
                            ['id' => 1, 'options' => ['\sqrt{41}', '\sqrt{48}', '\sqrt{53}', '\sqrt{63}']],
                            ['id' => 2, 'options' => ['\sqrt{28}', '\sqrt{33}', '\sqrt{38}', '\sqrt{47}']],
                            ['id' => 3, 'options' => ['\sqrt{17}', '\sqrt{22}', '\sqrt{28}', '\sqrt{32}']],
                            ['id' => 4, 'options' => ['\sqrt{29}', '\sqrt{33}', '\sqrt{39}', '\sqrt{44}']],
                            ['id' => 5, 'options' => ['\sqrt{18}', '\sqrt{24}', '\sqrt{26}', '\sqrt{32}']],
                            ['id' => 6, 'options' => ['\sqrt{40}', '\sqrt{46}', '\sqrt{53}', '\sqrt{58}']],
                        ]
                    ],
                    // Задание 10 - Сколько целых чисел между
                    [
                        'number' => 10,
                        'instruction' => 'Сколько целых чисел расположено между...',
                        'type' => 'count_integers',
                        'tasks' => [
                            ['id' => 1, 'left' => '\sqrt{5}', 'right' => '\sqrt{95}'],
                            ['id' => 2, 'left' => '\sqrt{19}', 'right' => '\sqrt{133}'],
                            ['id' => 3, 'left' => '\sqrt{18}', 'right' => '\sqrt{78}'],
                            ['id' => 4, 'left' => '\sqrt{17}', 'right' => '\sqrt{114}'],
                            ['id' => 5, 'left' => '6^7', 'right' => '7^6'],
                            ['id' => 6, 'left' => '3^{14}', 'right' => '7^3'],
                            ['id' => 7, 'left' => '2^{10}', 'right' => '10^2'],
                            ['id' => 8, 'left' => '4^{11}', 'right' => '11^2'],
                        ]
                    ],
                ]
            ],

            // =====================
            // БЛОК 3. Типовые экзаменационные варианты
            // =====================
            [
                'number' => 3,
                'title' => 'Типовые экзаменационные варианты',
                'zadaniya' => [
                    // Задание 1 - Принадлежность отрезку (отрицательные)
                    [
                        'number' => 1,
                        'instruction' => 'Какое из данных чисел принадлежит отрезку...',
                        'type' => 'negative_segment',
                        'tasks' => [
                            ['id' => 1, 'segment' => '[−4; −3]', 'options' => ['-\frac{45}{19}', '-\frac{52}{19}', '-\frac{68}{19}', '-\frac{77}{19}']],
                            ['id' => 2, 'segment' => '[−7; −6]', 'options' => ['-\frac{68}{13}', '-\frac{82}{13}', '-\frac{92}{13}', '-\frac{101}{13}']],
                            ['id' => 3, 'segment' => '[−8; −7]', 'options' => ['-\frac{69}{11}', '-\frac{80}{11}', '-\frac{90}{11}', '-\frac{92}{11}']],
                            ['id' => 4, 'segment' => '[−9; −8]', 'options' => ['-\frac{46}{7}', '-\frac{53}{7}', '-\frac{55}{7}', '-\frac{61}{7}']],
                        ]
                    ],
                    // Задание 2 - Точка для дроби 3/10
                    [
                        'number' => 2,
                        'instruction' => 'На координатной прямой точки A, B, C и D соответствуют числам $-\frac{3}{8}$; $\frac{3}{10}$; $-\frac{3}{7}$; $\frac{3}{14}$. Какой точке соответствует число $\frac{3}{10}$?',
                        'type' => 'simple_choice',
                        'options' => ['A', 'B', 'C', 'D'],
                    ],
                    // Задание 3 - Точка для дроби 5/12
                    [
                        'number' => 3,
                        'instruction' => 'На координатной прямой точки A, B, C и D соответствуют числам $-\frac{5}{6}$; $\frac{5}{12}$; $\frac{5}{6}$; $\frac{5}{10}$. Какой точке соответствует число $\frac{5}{12}$?',
                        'type' => 'simple_choice',
                        'options' => ['A', 'B', 'C', 'D'],
                    ],
                    // Задание 4 - Точка для дроби -4/7
                    [
                        'number' => 4,
                        'instruction' => 'На координатной прямой точки A, B, C и D соответствуют числам $-\frac{4}{5}$; $-\frac{4}{9}$; $\frac{4}{7}$; $-\frac{4}{7}$. Какой точке соответствует число $-\frac{4}{7}$?',
                        'type' => 'simple_choice',
                        'options' => ['A', 'B', 'C', 'D'],
                    ],
                    // Задание 5 - Точка для дроби -2/9
                    [
                        'number' => 5,
                        'instruction' => 'На координатной прямой точки A, B, C и D соответствуют числам $\frac{2}{7}$; $\frac{2}{11}$; $-\frac{2}{11}$; $-\frac{2}{9}$. Какой точке соответствует число $-\frac{2}{9}$?',
                        'type' => 'simple_choice',
                        'options' => ['A', 'B', 'C', 'D'],
                    ],
                    // Задание 6 - Между какими целыми (отрицательные дроби)
                    [
                        'number' => 6,
                        'instruction' => 'Между какими целыми числами заключено число...',
                        'type' => 'negative_interval',
                        'tasks' => [
                            ['id' => 1, 'expression' => '-\frac{134}{11}', 'options' => ['–11 и –10', '–12 и –11', '–13 и –12', '–14 и –13']],
                            ['id' => 2, 'expression' => '-\frac{104}{9}', 'options' => ['–12 и –11', '–13 и –12', '–14 и –13', '–15 и –14']],
                            ['id' => 3, 'expression' => '-\frac{111}{17}', 'options' => ['–6 и –5', '–7 и –6', '–8 и –7', '–9 и –8']],
                            ['id' => 4, 'expression' => '-\frac{152}{15}', 'options' => ['–8 и –7', '–9 и –8', '–10 и –9', '–11 и –10']],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Parse PDF file and return JSON
     */
    public function parsePdf(Request $request)
    {
        $pdfPath = storage_path('app/pdf/task_06.pdf');

        if (!file_exists($pdfPath)) {
            return response()->json([
                'error' => 'PDF file not found',
                'path' => $pdfPath,
            ], 404);
        }

        try {
            $parser = new PdfTaskParser();
            $blocks = $parser->parseTask06($pdfPath);

            return response()->json([
                'status' => 'ok',
                'blocks' => $blocks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
