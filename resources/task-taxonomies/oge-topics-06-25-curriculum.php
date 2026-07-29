<?php

/**
 * Учебный порядок исходных подтипов ФИПИ.
 *
 * Подтипы используются только как воспроизводимая граница при сборке
 * точных GUID-манифестов. Названия и порядок здесь курируются по знаниям
 * и способам решения, а не копируют технические заголовки ФИПИ.
 */
return [
    '06' => [
        'sections' => [
            [
                'title' => 'Десятичные дроби',
                'groups' => [
                    [
                        'key' => 'decimal-fraction-arithmetic',
                        'title' => 'Действия с десятичными дробями',
                        'subtypes' => [2],
                    ],
                ],
            ],
            [
                'title' => 'Обыкновенные дроби',
                'groups' => [
                    [
                        'key' => 'common-fraction-arithmetic',
                        'title' => 'Умножение, деление, сложение и вычитание дробей',
                        'subtypes' => [1],
                    ],
                    [
                        'key' => 'compound-fraction',
                        'title' => 'Составная дробь: сначала вычислить знаменатель',
                        'subtypes' => [3],
                    ],
                ],
            ],
        ],
    ],
    '07' => [
        'sections' => [
            [
                'title' => 'Чтение координатной прямой',
                'groups' => [
                    [
                        'key' => 'number-position-and-sign',
                        'title' => 'Положение числа относительно нуля',
                        'subtypes' => [1, 2],
                    ],
                    [
                        'key' => 'difference-sign-by-point-order',
                        'title' => 'Знак разности по порядку точек',
                        'subtypes' => [3, 4, 5, 6, 7, 8, 9],
                    ],
                ],
            ],
            [
                'title' => 'Рациональные числа на прямой',
                'groups' => [
                    [
                        'key' => 'locate-fraction-or-decimal',
                        'title' => 'Найти дробь или десятичное число среди отмеченных точек',
                        'subtypes' => [10, 11, 16, 17, 21],
                    ],
                    [
                        'key' => 'integer-bounds-for-fraction',
                        'title' => 'Оценить дробь между соседними целыми числами',
                        'subtypes' => [12],
                    ],
                    [
                        'key' => 'compare-fractions',
                        'title' => 'Сравнить дроби и выбрать число между ними',
                        'subtypes' => [13],
                    ],
                    [
                        'key' => 'number-in-interval',
                        'title' => 'Определить принадлежность числовому промежутку',
                        'subtypes' => [14, 15],
                    ],
                ],
            ],
            [
                'title' => 'Приближённая оценка квадратного корня',
                'groups' => [
                    [
                        'key' => 'locate-square-root',
                        'title' => 'Найти квадратный корень среди точек',
                        'subtypes' => [18, 22],
                    ],
                    [
                        'key' => 'integer-bounds-for-square-root',
                        'title' => 'Оценить корень между соседними целыми числами',
                        'subtypes' => [19],
                    ],
                    [
                        'key' => 'square-root-in-interval',
                        'title' => 'Выбрать корень из заданного промежутка',
                        'subtypes' => [20],
                    ],
                ],
            ],
        ],
    ],
    '08' => [
        'sections' => [
            [
                'title' => 'Квадратный корень',
                'groups' => [
                    [
                        'key' => 'square-root-after-substitution',
                        'title' => 'Подставить значения и извлечь квадратный корень',
                        'subtypes' => [1, 2, 3, 4],
                    ],
                    [
                        'key' => 'multiply-and-divide-radicals',
                        'title' => 'Умножение и деление квадратных корней',
                        'subtypes' => [5, 6, 7, 8],
                    ],
                    [
                        'key' => 'square-root-of-even-power',
                        'title' => 'Квадратный корень из чётной степени',
                        'subtypes' => [9],
                    ],
                    [
                        'key' => 'conjugate-radical-expressions',
                        'title' => 'Сопряжённые выражения и разность квадратов',
                        'subtypes' => [10, 11, 12],
                    ],
                ],
            ],
            [
                'title' => 'Свойства степеней',
                'groups' => [
                    [
                        'key' => 'same-base-power-rules',
                        'title' => 'Умножение и деление степеней с одинаковым основанием',
                        'subtypes' => [13, 14],
                    ],
                    [
                        'key' => 'power-of-a-power-and-product',
                        'title' => 'Степень степени и степень произведения',
                        'subtypes' => [15, 16],
                    ],
                    [
                        'key' => 'factorized-numeric-powers',
                        'title' => 'Разложить основания на множители и сократить степени',
                        'subtypes' => [17, 18, 19],
                    ],
                    [
                        'key' => 'negative-integer-powers',
                        'title' => 'Отрицательные показатели степени',
                        'subtypes' => [20, 21, 22, 23, 24],
                    ],
                ],
            ],
        ],
    ],
    '09' => [
        'sections' => [
            [
                'title' => 'Линейные уравнения',
                'groups' => [
                    [
                        'key' => 'linear-equation',
                        'title' => 'Перенести слагаемые и привести подобные',
                        'subtypes' => [1, 4, 9],
                    ],
                ],
            ],
            [
                'title' => 'Неполные квадратные уравнения',
                'groups' => [
                    [
                        'key' => 'square-equals-number',
                        'title' => 'Уравнение вида x² = a',
                        'subtypes' => [2, 5],
                    ],
                    [
                        'key' => 'factor-out-variable',
                        'title' => 'Вынести x за скобки и применить правило нулевого произведения',
                        'subtypes' => [3, 10],
                    ],
                ],
            ],
            [
                'title' => 'Полные квадратные уравнения',
                'groups' => [
                    [
                        'key' => 'quadratic-equation-two-roots',
                        'title' => 'Найти корни и выбрать требуемый',
                        'subtypes' => [6, 7, 8],
                    ],
                ],
            ],
        ],
    ],
    '10' => [
        'sections' => [
            [
                'title' => 'Определение вероятности',
                'groups' => [
                    [
                        'key' => 'favourable-over-all-outcomes',
                        'title' => 'Число благоприятных исходов разделить на число всех исходов',
                        'subtypes' => [3, 5],
                    ],
                    [
                        'key' => 'empirical-probability',
                        'title' => 'Оценить вероятность по статистической частоте',
                        'subtypes' => [6],
                    ],
                ],
            ],
            [
                'title' => 'Простые случайные события',
                'groups' => [
                    [
                        'key' => 'single-random-choice',
                        'title' => 'Один случайный выбор из предметов разных видов',
                        'subtypes' => [2],
                    ],
                    [
                        'key' => 'conditional-second-choice',
                        'title' => 'Второй выбор при известном результате первого',
                        'subtypes' => [1],
                    ],
                    [
                        'key' => 'complementary-event',
                        'title' => 'Противоположное событие: 1 − P',
                        'subtypes' => [7, 8, 9],
                    ],
                ],
            ],
            [
                'title' => 'Сочетание событий',
                'groups' => [
                    [
                        'key' => 'euler-diagram-events',
                        'title' => 'Диаграмма Эйлера: объединение и пересечение событий',
                        'subtypes' => [4],
                    ],
                ],
            ],
        ],
    ],
    '11' => [
        'sections' => [
            [
                'title' => 'Формула и вид графика',
                'groups' => [
                    [
                        'key' => 'match-function-and-graph',
                        'title' => 'Сопоставить формулу функции с её графиком',
                        'subtypes' => [1, 6],
                    ],
                ],
            ],
            [
                'title' => 'Линейная функция',
                'groups' => [
                    [
                        'key' => 'linear-coefficient-signs',
                        'title' => 'Определить знаки k и b по прямой',
                        'subtypes' => [2, 3],
                    ],
                ],
            ],
            [
                'title' => 'Квадратичная функция',
                'groups' => [
                    [
                        'key' => 'quadratic-coefficient-signs',
                        'title' => 'Определить знаки a и c по параболе',
                        'subtypes' => [4, 5],
                    ],
                ],
            ],
        ],
    ],
    '12' => [
        'sections' => [
            [
                'title' => 'Линейные формулы',
                'groups' => [
                    [
                        'key' => 'linear-formula-substitution',
                        'title' => 'Подставить значение в линейную формулу',
                        'subtypes' => [1, 3],
                    ],
                    [
                        'key' => 'linear-formula-inverse',
                        'title' => 'Выразить неизвестную из линейной формулы',
                        'subtypes' => [2, 4],
                    ],
                ],
            ],
            [
                'title' => 'Формулы с произведением величин',
                'groups' => [
                    [
                        'key' => 'product-formula',
                        'title' => 'Найти множитель из формулы произведения',
                        'subtypes' => [5, 6, 7],
                    ],
                ],
            ],
            [
                'title' => 'Формулы с квадратом величины',
                'groups' => [
                    [
                        'key' => 'square-variable-formula',
                        'title' => 'Выразить величину и извлечь квадратный корень',
                        'subtypes' => [8, 9, 10],
                    ],
                ],
            ],
        ],
    ],
    '13' => [
        'sections' => [
            [
                'title' => 'Сравнение чисел',
                'groups' => [
                    [
                        'key' => 'inequality-from-number-line',
                        'title' => 'Определить верное неравенство по положению точек',
                        'subtypes' => [11],
                    ],
                ],
            ],
            [
                'title' => 'Линейные неравенства',
                'groups' => [
                    [
                        'key' => 'linear-inequality',
                        'title' => 'Перенести слагаемые и учесть знак коэффициента',
                        'subtypes' => [4],
                    ],
                    [
                        'key' => 'linear-inequality-system',
                        'title' => 'Пересечь решения системы неравенств',
                        'subtypes' => [5],
                    ],
                    [
                        'key' => 'inequality-by-solution-graph',
                        'title' => 'Восстановить неравенство по изображённому решению',
                        'subtypes' => [9],
                    ],
                ],
            ],
            [
                'title' => 'Квадратные неравенства',
                'groups' => [
                    [
                        'key' => 'factorized-quadratic-inequality',
                        'title' => 'Разложить на множители и определить знаки промежутков',
                        'subtypes' => [1, 2, 3, 6, 7, 8, 10],
                    ],
                ],
            ],
        ],
    ],
    '14' => [
        'sections' => [
            [
                'title' => 'Арифметическая прогрессия',
                'groups' => [
                    [
                        'key' => 'arithmetic-progression-term',
                        'title' => 'Найти член прогрессии по первому члену и разности',
                        'subtypes' => [1, 3, 4, 5, 7],
                    ],
                    [
                        'key' => 'arithmetic-progression-term-from-two-known',
                        'title' => 'Восстановить разность по двум известным членам',
                        'subtypes' => [2],
                    ],
                    [
                        'key' => 'arithmetic-progression-sum',
                        'title' => 'Найти сумму первых членов прогрессии',
                        'subtypes' => [6, 8, 9, 10],
                    ],
                ],
            ],
            [
                'title' => 'Геометрическая прогрессия',
                'groups' => [
                    [
                        'key' => 'geometric-decay',
                        'title' => 'Повторное уменьшение в одно и то же число раз',
                        'subtypes' => [11, 12, 14, 15],
                    ],
                    [
                        'key' => 'geometric-growth',
                        'title' => 'Повторное увеличение в одно и то же число раз',
                        'subtypes' => [13],
                    ],
                ],
            ],
        ],
    ],
];
