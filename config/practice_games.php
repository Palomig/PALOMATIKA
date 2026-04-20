<?php

return [
    'mini_games' => [
        'equations' => [
            'slug' => 'equations',
            'title' => 'Уравнения',
            'description' => 'Мини-игра на перенос через равно и типичные ошибки со знаками и делением.',
            'icon' => '🧮',
            'turn_seconds' => 10,
            'intro' => [
                'eyebrow' => 'Мини-игра',
                'title' => 'Перенос через равно',
                'description' => 'Выбирай правильный следующий шаг. На каждый ход есть 10 секунд, игра идёт до первой ошибки.',
                'rules' => [
                    '10 секунд на ход',
                    'Счётчик правильных ответов сверху',
                    'Сложность растёт каждые 10 правильных ответов',
                ],
            ],
            'theory' => [
                'title' => 'Основные правила переноса',
                'items' => [
                    'При переносе слагаемого через равно знак меняется на противоположный.',
                    'При переносе множителя знак не переворачивается сам по себе: меняется действие, было умножение, стало деление.',
                    'Если коэффициент отрицательный, при делении он остаётся отрицательным: -4x = 8 -> x = 8 / -4.',
                    'При делении сверху всегда правая часть, снизу — коэффициент: 8x = 2 -> x = 2/8, а не 8/2, даже если дробь меньше единицы.',
                ],
            ],
            'levels' => [
                [
                    'level' => 1,
                    'score_from' => 0,
                    'score_to' => 9,
                    'task_types' => [
                        'move_positive_term',
                        'move_negative_term',
                    ],
                ],
                [
                    'level' => 2,
                    'score_from' => 10,
                    'score_to' => 19,
                    'task_types' => [
                        'move_negative_term',
                        'move_negative_term_before_x',
                        'move_positive_term_after_x',
                    ],
                ],
                [
                    'level' => 3,
                    'score_from' => 20,
                    'score_to' => 29,
                    'task_types' => [
                        'move_positive_multiplier',
                        'move_negative_multiplier',
                        'move_positive_multiplier_small_result',
                        'move_negative_multiplier_small_result',
                    ],
                ],
                [
                    'level' => 4,
                    'score_from' => 30,
                    'score_to' => 39,
                    'task_types' => [
                        'move_negative_term_before_x',
                        'move_positive_term_after_x',
                        'move_negative_multiplier',
                        'move_positive_multiplier_small_result',
                    ],
                ],
                [
                    'level' => 5,
                    'score_from' => 40,
                    'score_to' => null,
                    'task_types' => [
                        'move_negative_term_after_constant',
                        'move_negative_term_before_x',
                        'move_negative_multiplier',
                        'move_positive_multiplier_small_result',
                        'move_negative_multiplier_small_result',
                    ],
                ],
            ],
        ],
        'functions' => [
            'slug' => 'functions',
            'title' => 'Графики функций',
            'description' => 'Мини-игра на определение знаков коэффициентов по графику линейной функции и параболы.',
            'icon' => '📈',
            'turn_seconds' => 15,
            'intro' => [
                'eyebrow' => 'Мини-игра',
                'title' => 'Знаки коэффициентов',
                'description' => 'По графику определи знак коэффициента. На каждый ход 15 секунд, игра идёт до первой ошибки.',
                'rules' => [
                    '15 секунд на ход',
                    'Уровень 1 — линейная функция, уровень 2 — парабола',
                    'Спрашивают только знак: > 0 или < 0',
                ],
            ],
            'theory' => [
                'title' => 'Как читать график',
                'items' => [
                    'y = kx + b: k — наклон прямой (k > 0 — растёт, k < 0 — убывает).',
                    'y = kx + b: b — где прямая пересекает ось Y (выше нуля или ниже).',
                    'y = ax² + c: a — куда направлены ветви параболы (a > 0 — вверх, a < 0 — вниз).',
                    'y = ax² + c: c — сдвиг вершины по оси Y (c — это y-координата вершины).',
                ],
            ],
            'levels' => [
                [
                    'level' => 1,
                    'score_from' => 0,
                    'score_to' => 9,
                    'task_types' => [
                        'linear_sign_k',
                        'linear_sign_b',
                    ],
                ],
                [
                    'level' => 2,
                    'score_from' => 10,
                    'score_to' => null,
                    'task_types' => [
                        'parabola_sign_a',
                        'parabola_sign_c',
                    ],
                ],
            ],
        ],
    ],
];
