<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestPdfController extends Controller
{
    /**
     * Display parsed tasks from PDF for topic 06
     */
    public function topic06()
    {
        // Data structure based on PDF screenshots
        // Format: Block -> Zadanie -> Tasks

        $blocks = [
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    [
                        'number' => 1,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            // Row 1
                            ['id' => 1, 'expression' => '\frac{3}{4} \cdot \frac{6}{5}', 'answer' => '0.9'],
                            ['id' => 7, 'expression' => '\frac{12}{5} \cdot \frac{15}{2}', 'answer' => '18'],
                            ['id' => 13, 'expression' => '\frac{1}{4} \cdot \frac{3}{25}', 'answer' => '0.03'],
                            ['id' => 19, 'expression' => '\frac{14}{25} + \frac{3}{2}', 'answer' => '2.06'],
                            // Row 2
                            ['id' => 2, 'expression' => '\frac{21}{5} \cdot \frac{3}{7}', 'answer' => '1.8'],
                            ['id' => 8, 'expression' => '\frac{6}{5} : \frac{4}{11}', 'answer' => '3.3'],
                            ['id' => 14, 'expression' => '\frac{1}{5} \cdot \frac{27}{50}', 'answer' => '0.108'],
                            ['id' => 20, 'expression' => '\frac{9}{4} + \frac{8}{5}', 'answer' => '3.85'],
                            // Row 3
                            ['id' => 3, 'expression' => '\frac{3}{5} \cdot \frac{25}{4}', 'answer' => '3.75'],
                            ['id' => 9, 'expression' => '\frac{3}{4} : \frac{4}{35}', 'answer' => '6.5625'],
                            ['id' => 15, 'expression' => '\frac{1}{2} \cdot \frac{9}{25}', 'answer' => '0.18'],
                            ['id' => 21, 'expression' => '\frac{11}{5} + \frac{13}{4}', 'answer' => '5.45'],
                            // Row 4
                            ['id' => 4, 'expression' => '\frac{9}{5} \cdot \frac{2}{3}', 'answer' => '1.2'],
                            ['id' => 10, 'expression' => '\frac{15}{4} \cdot \frac{3}{7}', 'answer' => '1.607'],
                            ['id' => 16, 'expression' => '\frac{1}{5} \cdot \frac{3}{4}', 'answer' => '0.15'],
                            ['id' => 22, 'expression' => '\frac{1}{10} + \frac{21}{50}', 'answer' => '0.52'],
                            // Row 5
                            ['id' => 5, 'expression' => '\frac{5}{3} \cdot \frac{9}{2}', 'answer' => '7.5'],
                            ['id' => 11, 'expression' => '\frac{21}{2} \cdot \frac{3}{5}', 'answer' => '6.3'],
                            ['id' => 17, 'expression' => '\frac{1}{3} \cdot \frac{13}{50}', 'answer' => '0.0867'],
                            ['id' => 23, 'expression' => '\frac{3}{7} \cdot 25', 'answer' => '10.714'],
                            // Row 6
                            ['id' => 6, 'expression' => '\frac{7}{5} \cdot \frac{12}{35}', 'answer' => '0.48'],
                            ['id' => 12, 'expression' => '\frac{14}{5} \cdot \frac{7}{2}', 'answer' => '9.8'],
                            ['id' => 18, 'expression' => '\frac{1}{10} \cdot \frac{23}{20}', 'answer' => '0.115'],
                            ['id' => 24, 'expression' => '\frac{4}{25} + \frac{15}{4}', 'answer' => '3.91'],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            // Decimal operations
                            ['id' => 1, 'expression' => '9{,}3 + 7{,}8', 'answer' => '17.1'],
                            ['id' => 4, 'expression' => '5{,}7 - 7{,}6', 'answer' => '-1.9'],
                            ['id' => 7, 'expression' => '5{,}2 \cdot 3{,}1', 'answer' => '16.12'],
                            ['id' => 10, 'expression' => '\frac{8{,}2}{4{,}1}', 'answer' => '2'],

                            ['id' => 2, 'expression' => '8{,}7 + 4{,}6', 'answer' => '13.3'],
                            ['id' => 5, 'expression' => '4{,}9 - 9{,}4', 'answer' => '-4.5'],
                            ['id' => 8, 'expression' => '2{,}1 \cdot 9{,}6', 'answer' => '20.16'],
                            ['id' => 11, 'expression' => '\frac{13{,}2}{1{,}2}', 'answer' => '11'],

                            ['id' => 3, 'expression' => '6{,}9 + 7{,}4', 'answer' => '14.3'],
                            ['id' => 6, 'expression' => '6{,}1 - 2{,}5', 'answer' => '3.6'],
                            ['id' => 9, 'expression' => '8{,}9 \cdot 4{,}3', 'answer' => '38.27'],
                            ['id' => 12, 'expression' => '\frac{6{,}5}{1{,}3}', 'answer' => '5'],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Представьте выражение в виде дроби с указанным знаменателем. В ответ запишите числитель полученной дроби.',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{7}{9} - \frac{2}{5}', 'denominator' => 90, 'answer' => '17'],
                            ['id' => 2, 'expression' => '\frac{6}{7} - \frac{3}{5}', 'denominator' => 70, 'answer' => '9'],
                            ['id' => 3, 'expression' => '\frac{1}{7} + \frac{3}{4}', 'denominator' => 56, 'answer' => '29'],
                            ['id' => 4, 'expression' => '\frac{5}{8} + \frac{1}{3}', 'denominator' => 48, 'answer' => '31'],
                        ]
                    ]
                ]
            ]
        ];

        return view('test.topic06', compact('blocks'));
    }

    /**
     * Parse PDF file and extract tasks
     */
    public function parsePdf(Request $request)
    {
        $pdfPath = storage_path('app/pdf/06_fractions.pdf');

        if (!file_exists($pdfPath)) {
            return response()->json([
                'error' => 'PDF file not found',
                'path' => $pdfPath,
                'hint' => 'Upload PDF files to storage/app/pdf/'
            ], 404);
        }

        // TODO: Implement PDF parsing with pdftotext or similar
        // For now, return manual data

        return response()->json([
            'status' => 'ok',
            'message' => 'PDF parsing not yet implemented'
        ]);
    }
}
