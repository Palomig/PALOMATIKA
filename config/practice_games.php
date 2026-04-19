<?php

return [
    'mini_games' => [
        'equations' => [
            'slug' => 'equations',
            'title' => 'Уравнения',
            'description' => 'Мини-игра на перенос через равно и типичные ошибки со знаками и делением.',
            'icon' => '🧮',
            'accent' => 'green',
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
                    ],
                ],
            ],
        ],
    ],
];
