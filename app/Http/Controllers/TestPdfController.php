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
        $pdfPath = storage_path('app/pdf/task_06.pdf');

        // Check if PDF exists
        if (file_exists($pdfPath)) {
            try {
                $parser = new PdfTaskParser();
                $blocks = $parser->parseTask06($pdfPath);
                $source = 'PDF Parser';
            } catch (\Exception $e) {
                $blocks = $this->getManualData();
                $source = 'Manual (parser error: ' . $e->getMessage() . ')';
            }
        } else {
            $blocks = $this->getManualData();
            $source = 'Manual (PDF not found)';
        }

        return view('test.topic06', compact('blocks', 'source'));
    }

    /**
     * Get manual data as fallback
     */
    protected function getManualData(): array
    {
        return [
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
                            ['id' => 7, 'expression' => '\frac{12}{5} : \frac{15}{2}', 'answer' => '0.8'],
                            ['id' => 13, 'expression' => '\frac{1}{4} \cdot \frac{3}{25}', 'answer' => '0.03'],
                            ['id' => 19, 'expression' => '\frac{14}{25} + \frac{3}{2}', 'answer' => '2.06'],
                            // Row 2
                            ['id' => 2, 'expression' => '\frac{21}{5} \cdot \frac{3}{7}', 'answer' => '1.8'],
                            ['id' => 8, 'expression' => '\frac{6}{5} : \frac{4}{11}', 'answer' => '3.3'],
                            ['id' => 14, 'expression' => '\frac{1}{5} \cdot \frac{27}{50}', 'answer' => '0.108'],
                            ['id' => 20, 'expression' => '\frac{9}{4} + \frac{8}{5}', 'answer' => '3.85'],
                            // Row 3
                            ['id' => 3, 'expression' => '\frac{3}{5} \cdot \frac{25}{4}', 'answer' => '3.75'],
                            ['id' => 9, 'expression' => '\frac{3}{5} : \frac{4}{35}', 'answer' => '5.25'],
                            ['id' => 15, 'expression' => '\frac{1}{2} \cdot \frac{9}{25}', 'answer' => '0.18'],
                            ['id' => 21, 'expression' => '\frac{11}{5} + \frac{13}{4}', 'answer' => '5.45'],
                            // Row 4
                            ['id' => 4, 'expression' => '\frac{9}{5} \cdot \frac{2}{3}', 'answer' => '1.2'],
                            ['id' => 10, 'expression' => '\frac{15}{4} : \frac{3}{7}', 'answer' => '8.75'],
                            ['id' => 16, 'expression' => '\frac{1}{5} \cdot \frac{3}{4}', 'answer' => '0.15'],
                            ['id' => 22, 'expression' => '\frac{1}{10} + \frac{21}{50}', 'answer' => '0.52'],
                            // Row 5
                            ['id' => 5, 'expression' => '\frac{5}{3} \cdot \frac{9}{2}', 'answer' => '7.5'],
                            ['id' => 11, 'expression' => '\frac{21}{2} : \frac{3}{5}', 'answer' => '17.5'],
                            ['id' => 17, 'expression' => '\frac{1}{2} \cdot \frac{13}{50}', 'answer' => '0.13'],
                            ['id' => 23, 'expression' => '\frac{3}{4} + \frac{7}{25}', 'answer' => '1.03'],
                            // Row 6
                            ['id' => 6, 'expression' => '\frac{7}{5} \cdot \frac{12}{35}', 'answer' => '0.48'],
                            ['id' => 12, 'expression' => '\frac{14}{5} : \frac{7}{2}', 'answer' => '0.8'],
                            ['id' => 18, 'expression' => '\frac{1}{10} \cdot \frac{23}{20}', 'answer' => '0.115'],
                            ['id' => 24, 'expression' => '\frac{4}{25} + \frac{15}{4}', 'answer' => '3.91'],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
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
                            ['id' => 5, 'expression' => '\frac{3}{4} - \frac{8}{11}', 'denominator' => 88, 'answer' => '1'],
                            ['id' => 6, 'expression' => '\frac{2}{3} - \frac{7}{13}', 'denominator' => 78, 'answer' => '5'],
                        ]
                    ],
                    [
                        'number' => 4,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{1}{\frac{1}{30} + \frac{1}{42}}', 'answer' => '17.5'],
                            ['id' => 2, 'expression' => '\frac{1}{\frac{1}{36} - \frac{1}{44}}', 'answer' => '99'],
                            ['id' => 3, 'expression' => '\frac{1}{\frac{1}{36} + \frac{1}{45}}', 'answer' => '20'],
                            ['id' => 4, 'expression' => '\frac{1}{\frac{1}{35} - \frac{1}{60}}', 'answer' => '84'],
                            ['id' => 5, 'expression' => '\frac{1}{\frac{1}{21} + \frac{1}{28}}', 'answer' => '12'],
                            ['id' => 6, 'expression' => '\frac{1}{\frac{1}{72} - \frac{1}{99}}', 'answer' => '264'],
                        ]
                    ]
                ]
            ]
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
                'hint' => 'Extract PDF from git: git show "b8c1426:РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №06 (трен) v2.pdf" > storage/app/pdf/task_06.pdf'
            ], 404);
        }

        try {
            $parser = new PdfTaskParser();
            $blocks = $parser->parseTask06($pdfPath);

            return response()->json([
                'status' => 'ok',
                'blocks' => $blocks,
                'raw_text_preview' => substr($parser->getRawText(), 0, 1000)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
