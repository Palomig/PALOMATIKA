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
    '15' => [
        'sections' => [
            [
                'title' => 'Углы в треугольнике',
                'groups' => [
                    [
                        'key' => 'triangle-angle-sum-and-exterior',
                        'title' => 'Сумма углов и внешний угол треугольника',
                        'subtypes' => [5, 6],
                    ],
                    [
                        'key' => 'isosceles-triangle-angles',
                        'title' => 'Углы при основании равнобедренного треугольника',
                        'subtypes' => [7],
                    ],
                    [
                        'key' => 'right-triangle-acute-angles',
                        'title' => 'Острые углы прямоугольного треугольника',
                        'subtypes' => [8, 9],
                    ],
                    [
                        'key' => 'angles-with-bisector-or-median',
                        'title' => 'Углы при биссектрисе или медиане',
                        'subtypes' => [1, 2, 3],
                    ],
                ],
            ],
            [
                'title' => 'Отрезки и подобие',
                'groups' => [
                    [
                        'key' => 'median-halves-side',
                        'title' => 'Медиана делит сторону пополам',
                        'subtypes' => [4],
                    ],
                    [
                        'key' => 'triangle-midline',
                        'title' => 'Средняя линия и подобие треугольников',
                        'subtypes' => [12],
                    ],
                    [
                        'key' => 'pythagorean-theorem',
                        'title' => 'Теорема Пифагора: найти гипотенузу или катет',
                        'subtypes' => [13, 14],
                    ],
                ],
            ],
            [
                'title' => 'Равносторонний треугольник',
                'groups' => [
                    [
                        'key' => 'equilateral-altitude-median-bisector',
                        'title' => 'Высота, медиана и биссектриса равностороннего треугольника',
                        'subtypes' => [15, 16, 17, 18, 19, 20],
                    ],
                ],
            ],
            [
                'title' => 'Тригонометрия прямоугольного треугольника',
                'groups' => [
                    [
                        'key' => 'trig-ratio-from-sides',
                        'title' => 'Найти синус, косинус или тангенс по сторонам',
                        'subtypes' => [21, 22, 23],
                    ],
                    [
                        'key' => 'side-from-trig-ratio',
                        'title' => 'Найти сторону по синусу, косинусу или тангенсу',
                        'subtypes' => [24, 25, 26],
                    ],
                ],
            ],
            [
                'title' => 'Площадь треугольника',
                'groups' => [
                    [
                        'key' => 'triangle-area-base-height',
                        'title' => 'Половина произведения основания на высоту',
                        'subtypes' => [10, 11],
                    ],
                    [
                        'key' => 'triangle-area-two-sides-sine',
                        'title' => 'Половина произведения двух сторон на синус угла',
                        'subtypes' => [27],
                    ],
                ],
            ],
        ],
    ],
    '17' => [
        'sections' => [
            [
                'title' => 'Параллелограмм',
                'groups' => [
                    [
                        'key' => 'parallelogram-angles',
                        'title' => 'Соседние углы и биссектриса параллелограмма',
                        'subtypes' => [6, 7, 8],
                    ],
                    [
                        'key' => 'parallelogram-diagonals',
                        'title' => 'Диагонали параллелограмма делятся пополам',
                        'subtypes' => [9],
                    ],
                    [
                        'key' => 'parallelogram-area',
                        'title' => 'Площадь параллелограмма и его высоты',
                        'subtypes' => [23, 24],
                    ],
                ],
            ],
            [
                'title' => 'Прямоугольник и квадрат',
                'groups' => [
                    [
                        'key' => 'rectangle-diagonal-angles',
                        'title' => 'Угол между диагоналями прямоугольника',
                        'subtypes' => [19],
                    ],
                    [
                        'key' => 'square-diagonal',
                        'title' => 'Диагональ квадрата через его сторону',
                        'subtypes' => [29],
                    ],
                ],
            ],
            [
                'title' => 'Ромб',
                'groups' => [
                    [
                        'key' => 'rhombus-angle-bisectors',
                        'title' => 'Диагонали ромба делят его углы пополам',
                        'subtypes' => [2, 3, 5, 20, 21],
                    ],
                    [
                        'key' => 'rhombus-height',
                        'title' => 'Высота ромба через сторону и угол',
                        'subtypes' => [22],
                    ],
                    [
                        'key' => 'rhombus-area',
                        'title' => 'Площадь ромба через диагонали, сторону или угол',
                        'subtypes' => [27, 28],
                    ],
                ],
            ],
            [
                'title' => 'Трапеция',
                'groups' => [
                    [
                        'key' => 'trapezoid-angles',
                        'title' => 'Углы равнобедренной и прямоугольной трапеции',
                        'subtypes' => [1, 4, 10, 11, 12, 13, 14],
                    ],
                    [
                        'key' => 'isosceles-trapezoid-base-height',
                        'title' => 'Основания и высота равнобедренной трапеции',
                        'subtypes' => [15, 16, 17, 18],
                    ],
                    [
                        'key' => 'trapezoid-midline',
                        'title' => 'Средняя линия трапеции и её части',
                        'subtypes' => [30],
                    ],
                    [
                        'key' => 'trapezoid-area',
                        'title' => 'Площадь трапеции',
                        'subtypes' => [25, 26],
                    ],
                ],
            ],
        ],
    ],
    '18' => [
        'sections' => [
            [
                'title' => 'Длины на клетчатой бумаге',
                'groups' => [
                    [
                        'key' => 'count-grid-length',
                        'title' => 'Определить длину по клеткам',
                        'subtypes' => [1, 2],
                    ],
                    [
                        'key' => 'segment-ratio-on-grid',
                        'title' => 'Сравнить длины отрезков',
                        'subtypes' => [3, 4],
                    ],
                    [
                        'key' => 'pythagorean-distance-on-grid',
                        'title' => 'Наклонный отрезок: применить теорему Пифагора',
                        'subtypes' => [5, 7, 8],
                    ],
                ],
            ],
            [
                'title' => 'Средняя линия',
                'groups' => [
                    [
                        'key' => 'midline-on-grid',
                        'title' => 'Средняя линия треугольника или трапеции',
                        'subtypes' => [6, 9],
                    ],
                ],
            ],
            [
                'title' => 'Площади на клетчатой бумаге',
                'groups' => [
                    [
                        'key' => 'polygon-area-on-grid',
                        'title' => 'Площадь треугольника или четырёхугольника',
                        'subtypes' => [11, 12, 13, 14],
                    ],
                    [
                        'key' => 'circle-area-ratio',
                        'title' => 'Отношение площадей кругов через радиусы',
                        'subtypes' => [10],
                    ],
                ],
            ],
        ],
    ],
    '19' => [
        'sections' => [
            [
                'title' => 'Одно верное утверждение',
                'groups' => [
                    [
                        'key' => 'single-true-geometry-statement',
                        'title' => 'Проверить утверждения и выбрать единственное верное',
                        'subtypes' => [1],
                    ],
                ],
            ],
            [
                'title' => 'Несколько верных утверждений',
                'groups' => [
                    [
                        'key' => 'multiple-true-geometry-statements',
                        'title' => 'Проверить каждое утверждение и записать все верные',
                        'subtypes' => [2],
                    ],
                ],
            ],
        ],
    ],
    '20' => [
        'sections' => [
            [
                'title' => 'Алгебраические преобразования',
                'groups' => [
                    [
                        'key' => 'expression-from-given-ratio',
                        'title' => 'Выразить нужную комбинацию из заданного отношения',
                        'subtypes' => [1],
                    ],
                ],
            ],
            [
                'title' => 'Уравнения',
                'groups' => [
                    [
                        'key' => 'factor-by-grouping',
                        'title' => 'Разложить многочлен на множители группировкой',
                        'subtypes' => [2],
                    ],
                    [
                        'key' => 'cancel-identical-radicals',
                        'title' => 'Сократить одинаковые радикалы и решить квадратное уравнение',
                        'subtypes' => [3],
                    ],
                    [
                        'key' => 'factorized-polynomial-equation',
                        'title' => 'Раскрыть структуру произведения и разложить на множители',
                        'subtypes' => [4],
                    ],
                    [
                        'key' => 'reciprocal-substitution',
                        'title' => 'Замена переменной для 1/x',
                        'subtypes' => [5],
                    ],
                    [
                        'key' => 'even-power-substitution',
                        'title' => 'Замена переменной для квадратов и четвёртых степеней',
                        'subtypes' => [6, 7],
                    ],
                ],
            ],
            [
                'title' => 'Системы уравнений',
                'groups' => [
                    [
                        'key' => 'substitution-in-system',
                        'title' => 'Приравнять два выражения для одной переменной',
                        'subtypes' => [8, 9],
                    ],
                    [
                        'key' => 'quadratic-system-elimination',
                        'title' => 'Исключить сумму квадратов из системы',
                        'subtypes' => [10, 11],
                    ],
                ],
            ],
            [
                'title' => 'Неравенства',
                'groups' => [
                    [
                        'key' => 'quadratic-inequality-after-shift',
                        'title' => 'Замена выражения и квадратное неравенство',
                        'subtypes' => [12],
                    ],
                    [
                        'key' => 'polynomial-interval-method',
                        'title' => 'Разложение на множители и метод интервалов',
                        'subtypes' => [13],
                    ],
                    [
                        'key' => 'rational-inequality-domain',
                        'title' => 'Рациональное неравенство с учётом области допустимых значений',
                        'subtypes' => [14],
                    ],
                ],
            ],
        ],
    ],
    '21' => [
        'sections' => [
            [
                'title' => 'Движение по прямой',
                'groups' => [
                    [
                        'key' => 'motion-time-difference',
                        'title' => 'Скорость и разность времени в пути',
                        'subtypes' => [1, 2, 3],
                        'rules' => [[
                            'subtype' => 10,
                            'pattern' => '/(велосипед|автомоб|бегун|поезд|город)/ui',
                        ]],
                    ],
                    [
                        'key' => 'circular-track-motion',
                        'title' => 'Движение по круговой трассе',
                        'subtypes' => [4, 5],
                    ],
                    [
                        'key' => 'average-speed',
                        'title' => 'Средняя скорость: весь путь разделить на всё время',
                        'subtypes' => [6, 7],
                    ],
                    [
                        'key' => 'train-relative-motion',
                        'title' => 'Длина поезда и относительная скорость',
                        'subtypes' => [8],
                    ],
                ],
            ],
            [
                'title' => 'Движение по воде',
                'groups' => [
                    [
                        'key' => 'river-current-motion',
                        'title' => 'Скорости по течению и против течения',
                        'subtypes' => [9, 11, 12],
                        'rules' => [[
                            'subtype' => 10,
                            'pattern' => '/(лодк|теплоход|барж|течени|пристан)/ui',
                        ]],
                    ],
                ],
            ],
            [
                'title' => 'Работа и производительность',
                'groups' => [
                    [
                        'key' => 'work-rate-equation',
                        'title' => 'Производительность рабочих или труб',
                        'subtypes' => [13],
                        'rules' => [[
                            'subtype' => 10,
                            'pattern' => '/(рабоч|труб|резервуар|детал)/ui',
                        ]],
                    ],
                ],
            ],
            [
                'title' => 'Смеси и концентрации',
                'groups' => [
                    [
                        'key' => 'mixture-mass-balance',
                        'title' => 'Сохранение массы сухого вещества или кислоты',
                        'rules' => [[
                            'subtype' => 10,
                            'pattern' => '/(фрукт|раствор|кислот|смес|сплав)/ui',
                        ]],
                    ],
                ],
            ],
        ],
    ],
    '22' => [
        'sections' => [
            [
                'title' => 'Рациональные функции и выколотые точки',
                'groups' => [
                    [
                        'key' => 'rational-cancellation-hole',
                        'title' => 'Сократить дробь и сохранить выколотую точку',
                        'subtypes' => [1, 4, 7],
                    ],
                ],
            ],
            [
                'title' => 'Функции с модулем',
                'groups' => [
                    [
                        'key' => 'absolute-value-rational-function',
                        'title' => 'Раскрыть модуль по знаку x и упростить дробь',
                        'subtypes' => [3, 5, 6, 8, 9],
                    ],
                    [
                        'key' => 'absolute-value-parabola',
                        'title' => 'Построить ветви параболы после раскрытия модуля',
                        'subtypes' => [2, 10, 11, 12, 13, 17, 18],
                    ],
                ],
            ],
            [
                'title' => 'Кусочно заданные функции',
                'groups' => [
                    [
                        'key' => 'piecewise-function-graph',
                        'title' => 'Построить каждую ветвь на своём промежутке',
                        'subtypes' => [14, 15, 16],
                    ],
                ],
            ],
        ],
    ],
    '23' => [
        'sections' => [
            [
                'title' => 'Треугольники',
                'groups' => [
                    [
                        'key' => 'right-triangle-altitude',
                        'title' => 'Высота к гипотенузе и подобие треугольников',
                        'subtypes' => [4, 8],
                    ],
                    [
                        'key' => 'parallel-lines-similarity',
                        'title' => 'Подобие при параллельных прямых',
                        'subtypes' => [6, 7],
                    ],
                ],
            ],
            [
                'title' => 'Параллелограмм и ромб',
                'groups' => [
                    [
                        'key' => 'parallelogram-angle-bisector',
                        'title' => 'Биссектриса угла параллелограмма и равнобедренный треугольник',
                        'subtypes' => [1],
                    ],
                    [
                        'key' => 'rhombus-diagonals-height',
                        'title' => 'Диагонали и высота ромба',
                        'subtypes' => [2, 3],
                    ],
                ],
            ],
            [
                'title' => 'Трапеция',
                'groups' => [
                    [
                        'key' => 'trapezoid-bisectors',
                        'title' => 'Биссектрисы углов трапеции',
                        'subtypes' => [5],
                    ],
                    [
                        'key' => 'trapezoid-side-by-angles',
                        'title' => 'Боковая сторона через высоту и углы',
                        'subtypes' => [9],
                    ],
                    [
                        'key' => 'trapezoid-parallel-section',
                        'title' => 'Параллельное сечение трапеции и пропорциональные отрезки',
                        'subtypes' => [10],
                    ],
                ],
            ],
            [
                'title' => 'Окружность',
                'groups' => [
                    [
                        'key' => 'circle-chords-distance',
                        'title' => 'Хорды и расстояния от центра',
                        'subtypes' => [11, 12],
                    ],
                    [
                        'key' => 'circle-in-right-triangle',
                        'title' => 'Окружность и высота прямоугольного треугольника',
                        'subtypes' => [13],
                    ],
                    [
                        'key' => 'cyclic-triangle-similarity',
                        'title' => 'Подобие треугольников при секущих окружности',
                        'subtypes' => [14],
                    ],
                    [
                        'key' => 'tangent-circle-radius',
                        'title' => 'Радиус к точке касания и прямоугольный треугольник',
                        'subtypes' => [15, 16, 19],
                    ],
                    [
                        'key' => 'circumcircle-sine-theorem',
                        'title' => 'Описанная окружность и расширенная теорема синусов',
                        'subtypes' => [17, 18],
                    ],
                ],
            ],
        ],
    ],
    '24' => [
        'sections' => [
            [
                'title' => 'Параллелограмм',
                'groups' => [
                    [
                        'key' => 'parallelogram-central-symmetry',
                        'title' => 'Центральная симметрия относительно пересечения диагоналей',
                        'subtypes' => [1],
                    ],
                    [
                        'key' => 'parallelogram-angle-bisectors',
                        'title' => 'Биссектрисы и равнобедренные треугольники',
                        'subtypes' => [2, 3, 4, 5],
                    ],
                    [
                        'key' => 'parallelogram-opposite-triangle-areas',
                        'title' => 'Сумма площадей противоположных треугольников',
                        'subtypes' => [6],
                    ],
                ],
            ],
            [
                'title' => 'Трапеция',
                'groups' => [
                    [
                        'key' => 'trapezoid-diagonal-areas',
                        'title' => 'Равные площади треугольников при диагоналях',
                        'subtypes' => [7],
                    ],
                    [
                        'key' => 'trapezoid-half-area',
                        'title' => 'Половина площади через середину стороны или среднюю линию',
                        'subtypes' => [8, 9],
                    ],
                    [
                        'key' => 'trapezoid-diagonal-similarity',
                        'title' => 'Подобие треугольников, образованных диагональю',
                        'subtypes' => [10],
                    ],
                ],
            ],
            [
                'title' => 'Подобие и вписанные углы',
                'groups' => [
                    [
                        'key' => 'cyclic-quadrilateral-similarity',
                        'title' => 'Подобие при продолжении сторон вписанного четырёхугольника',
                        'subtypes' => [11],
                    ],
                    [
                        'key' => 'triangle-altitudes-similarity',
                        'title' => 'Подобие и равные углы при высотах треугольника',
                        'subtypes' => [12, 13],
                    ],
                    [
                        'key' => 'equal-inscribed-angles',
                        'title' => 'Равные углы, опирающиеся на одну хорду',
                        'subtypes' => [14],
                    ],
                ],
            ],
            [
                'title' => 'Две окружности',
                'groups' => [
                    [
                        'key' => 'centers-line-perpendicular-chord',
                        'title' => 'Линия центров перпендикулярна общей хорде',
                        'subtypes' => [15, 16],
                    ],
                    [
                        'key' => 'common-tangent-similarity',
                        'title' => 'Общая касательная и отношение радиусов',
                        'subtypes' => [17],
                    ],
                ],
            ],
        ],
    ],
    '25' => [
        'sections' => [
            [
                'title' => 'Треугольники и их центры',
                'groups' => [
                    [
                        'key' => 'bisector-splits-altitude',
                        'title' => 'Биссектриса делит высоту: найти радиус описанной окружности',
                        'subtypes' => [2],
                    ],
                    [
                        'key' => 'circumcenter-perpendicular-construction',
                        'title' => 'Центр описанной окружности и перпендикуляр к радиусу',
                        'subtypes' => [3],
                    ],
                    [
                        'key' => 'semicircle-and-orthocenter',
                        'title' => 'Полуокружность на стороне и точка пересечения высот',
                        'subtypes' => [6],
                    ],
                    [
                        'key' => 'perpendicular-bisector-and-median',
                        'title' => 'Перпендикулярные биссектриса и медиана',
                        'subtypes' => [11],
                    ],
                ],
            ],
            [
                'title' => 'Параллелограмм',
                'groups' => [
                    [
                        'key' => 'parallelogram-bisectors-area',
                        'title' => 'Пересечение биссектрис и площадь параллелограмма',
                        'subtypes' => [1],
                    ],
                    [
                        'key' => 'parallelogram-triangle-incenter',
                        'title' => 'Инцентр треугольника внутри параллелограмма',
                        'subtypes' => [9],
                    ],
                ],
            ],
            [
                'title' => 'Трапеция',
                'groups' => [
                    [
                        'key' => 'trapezoid-tangent-circle',
                        'title' => 'Касающаяся окружность в прямоугольной трапеции',
                        'subtypes' => [4],
                    ],
                    [
                        'key' => 'trapezoid-right-angle-sum-circle',
                        'title' => 'Сумма углов 90° и радиус окружности',
                        'subtypes' => [5],
                    ],
                    [
                        'key' => 'trapezoid-bisector-area',
                        'title' => 'Биссектриса через середину стороны и площадь',
                        'subtypes' => [8],
                    ],
                    [
                        'key' => 'tangential-isosceles-trapezoid',
                        'title' => 'Вписанная окружность и диагонали равнобедренной трапеции',
                        'subtypes' => [10],
                    ],
                    [
                        'key' => 'trapezoid-midpoint-segments',
                        'title' => 'Отрезки между серединами сторон и основания трапеции',
                        'subtypes' => [13],
                    ],
                ],
            ],
            [
                'title' => 'Окружности и четырёхугольники',
                'groups' => [
                    [
                        'key' => 'equidistant-midpoint-circumcircle',
                        'title' => 'Равноудалённая середина как центр окружности',
                        'subtypes' => [7],
                    ],
                    [
                        'key' => 'two-circles-common-tangents',
                        'title' => 'Две касающиеся окружности и общие касательные',
                        'subtypes' => [12],
                    ],
                ],
            ],
        ],
    ],
];
