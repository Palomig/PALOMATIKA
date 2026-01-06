<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Advanced PDF Parser Service for OGE Task Books
 *
 * Automatically parses PDF files and creates structured data
 * suitable for web display similar to /test/6 and /test/7 pages.
 */
class AdvancedPdfParser
{
    protected PdfParserService $basicParser;
    protected string $storagePath;
    protected string $publicPath;

    public function __construct(PdfParserService $basicParser)
    {
        $this->basicParser = $basicParser;
        $this->storagePath = storage_path('app/pdf');
        $this->publicPath = public_path('images/tasks');
    }

    /**
     * Parse PDF and create fully structured data
     *
     * @param string $filename PDF filename
     * @param string $topicId Topic ID (e.g., '07')
     * @param string|null $title Optional title override
     * @return array Structured data ready for display
     */
    public function parseToStructured(string $filename, string $topicId, ?string $title = null): array
    {
        // Extract text and images
        $text = $this->basicParser->extractText($filename);
        $images = $this->basicParser->extractImages($filename, $topicId);
        $imageList = $this->basicParser->listImages($filename);

        // Parse structure
        $parsedTitle = $title ?? $this->extractTitle($text);
        $blocks = $this->parseBlocks($text, $imageList, $topicId);

        return [
            'topic_id' => $topicId,
            'title' => $parsedTitle,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'pdf_filename' => $filename,
            'images' => array_map(fn($img) => $img['filename'], $images),
            'images_count' => count($images),
            'structured_blocks' => $blocks,
            'text' => $text, // Keep raw text for reference
        ];
    }

    /**
     * Extract title from text (e.g., "07. Числа, координатная прямая")
     */
    protected function extractTitle(string $text): string
    {
        // Look for pattern like "07. Title" or "06. Title"
        if (preg_match('/^\s*(\d{2})\.\s*(.+)$/um', $text, $matches)) {
            return trim($matches[2]);
        }

        // Try to find in first few lines
        $lines = array_slice(explode("\n", $text), 0, 10);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^\d{2}\.\s*(.+)$/', $line, $matches)) {
                return trim($matches[1]);
            }
        }

        return 'Задание';
    }

    /**
     * Parse all blocks from text
     */
    protected function parseBlocks(string $text, array $imageList, string $topicId): array
    {
        $blocks = [];
        $lines = explode("\n", $text);
        $totalLines = count($lines);

        // Find all block positions
        $blockPositions = [];
        foreach ($lines as $lineNum => $line) {
            if (preg_match('/Блок\s*(\d+)\.\s*(.+)/u', $line, $matches)) {
                $blockPositions[] = [
                    'number' => (int)$matches[1],
                    'title' => trim($matches[2]),
                    'line' => $lineNum,
                ];
            }
        }

        // If no blocks found, treat entire document as one block
        if (empty($blockPositions)) {
            $blockPositions[] = [
                'number' => 1,
                'title' => 'Основной',
                'line' => 0,
            ];
        }

        // Calculate image distribution across blocks
        $imagesPerBlock = $this->distributeImages($imageList, count($blockPositions));
        $imageIndex = 0;

        // Parse each block
        foreach ($blockPositions as $idx => $blockInfo) {
            $startLine = $blockInfo['line'];
            $endLine = isset($blockPositions[$idx + 1]) ? $blockPositions[$idx + 1]['line'] : $totalLines;

            // Extract block text
            $blockText = implode("\n", array_slice($lines, $startLine, $endLine - $startLine));

            // Parse zadaniya in this block
            $blockImageCount = $imagesPerBlock[$idx] ?? 0;
            $zadaniya = $this->parseZadaniya($blockText, $topicId, $imageIndex, $blockImageCount);

            // Update image index for next block
            $imageIndex += $blockImageCount;

            $blocks[] = [
                'number' => $blockInfo['number'],
                'title' => $blockInfo['title'],
                'zadaniya' => $zadaniya,
            ];
        }

        return $blocks;
    }

    /**
     * Distribute images across blocks based on page distribution
     */
    protected function distributeImages(array $imageList, int $blockCount): array
    {
        if (empty($imageList) || $blockCount === 0) {
            return array_fill(0, $blockCount, 0);
        }

        $totalImages = count($imageList);

        // Group images by page
        $pages = array_unique(array_column($imageList, 'page'));
        $pagesCount = count($pages);

        if ($pagesCount <= $blockCount) {
            // Distribute evenly
            $perBlock = (int)ceil($totalImages / $blockCount);
            $distribution = [];
            $remaining = $totalImages;

            for ($i = 0; $i < $blockCount; $i++) {
                $count = min($perBlock, $remaining);
                $distribution[] = $count;
                $remaining -= $count;
            }

            return $distribution;
        }

        // More pages than blocks - distribute proportionally
        $perBlock = (int)floor($totalImages / $blockCount);
        $extra = $totalImages % $blockCount;

        $distribution = array_fill(0, $blockCount, $perBlock);
        for ($i = 0; $i < $extra; $i++) {
            $distribution[$i]++;
        }

        return $distribution;
    }

    /**
     * Parse zadaniya (tasks) from block text
     */
    protected function parseZadaniya(string $blockText, string $topicId, int $startImageIndex, int $imageCount): array
    {
        $zadaniya = [];
        $lines = explode("\n", $blockText);
        $totalLines = count($lines);

        // Find all zadanie positions
        $zadaniePositions = [];
        foreach ($lines as $lineNum => $line) {
            if (preg_match('/Задание\s*(\d+)\.\s*(.+)/u', $line, $matches)) {
                $zadaniePositions[] = [
                    'number' => (int)$matches[1],
                    'instruction' => trim($matches[2]),
                    'line' => $lineNum,
                ];
            }
        }

        if (empty($zadaniePositions)) {
            return $zadaniya;
        }

        // Calculate images per zadanie
        $imagesPerZadanie = $this->distributeImagesForZadaniya(count($zadaniePositions), $imageCount);
        $currentImageIndex = $startImageIndex;

        // Parse each zadanie
        foreach ($zadaniePositions as $idx => $zadanieInfo) {
            $startLine = $zadanieInfo['line'];
            $endLine = isset($zadaniePositions[$idx + 1]) ? $zadaniePositions[$idx + 1]['line'] : $totalLines;

            // Extract zadanie text
            $zadanieText = implode("\n", array_slice($lines, $startLine, $endLine - $startLine));

            // Extend instruction if it continues on next lines
            $instruction = $this->extractFullInstruction($zadanieText, $zadanieInfo['instruction']);

            // Determine type and parse tasks
            $type = $this->detectZadanieType($instruction, $zadanieText);
            $zadanieImageCount = $imagesPerZadanie[$idx] ?? 0;

            $tasks = $this->parseTasks($zadanieText, $type, $topicId, $currentImageIndex, $zadanieImageCount);
            $currentImageIndex += $zadanieImageCount;

            $zadanie = [
                'number' => $zadanieInfo['number'],
                'instruction' => $instruction,
                'type' => $type,
            ];

            // Add tasks or options depending on type
            if (!empty($tasks)) {
                $zadanie['tasks'] = $tasks;
            } else {
                // Try to parse as simple choice
                $options = $this->parseSimpleOptions($zadanieText);
                if (!empty($options)) {
                    $zadanie['options'] = $options;
                }
            }

            // Add image for simple zadanie if applicable
            if ($zadanieImageCount > 0 && empty($tasks)) {
                $zadanie['image'] = sprintf('img-%03d.png', $currentImageIndex - $zadanieImageCount);
            }

            $zadaniya[] = $zadanie;
        }

        return $zadaniya;
    }

    /**
     * Distribute images for zadaniya within a block
     */
    protected function distributeImagesForZadaniya(int $zadaniyaCount, int $imageCount): array
    {
        if ($zadaniyaCount === 0 || $imageCount === 0) {
            return array_fill(0, max(1, $zadaniyaCount), 0);
        }

        // Heuristic: first zadanie usually has more images (task variants)
        $distribution = [];
        $remaining = $imageCount;

        for ($i = 0; $i < $zadaniyaCount; $i++) {
            if ($i === 0 && $imageCount >= 6) {
                // First zadanie with images often has 6 variants
                $count = min(6, $remaining);
            } else {
                $count = (int)ceil($remaining / ($zadaniyaCount - $i));
            }
            $distribution[] = $count;
            $remaining -= $count;
        }

        return $distribution;
    }

    /**
     * Extract full instruction (may span multiple lines)
     */
    protected function extractFullInstruction(string $text, string $initialInstruction): string
    {
        $lines = explode("\n", $text);
        $instruction = $initialInstruction;

        // Check if instruction continues on next lines (doesn't start with number or special pattern)
        $foundStart = false;
        foreach ($lines as $line) {
            $line = trim($line);

            if (!$foundStart) {
                if (strpos($line, $initialInstruction) !== false) {
                    $foundStart = true;
                }
                continue;
            }

            // Stop if we hit task numbers, options, or empty line
            if (preg_match('/^\d+[)\.]/', $line) || preg_match('/^[1-6]\s*$/', $line) || empty($line)) {
                break;
            }

            // Continue instruction
            if (!preg_match('/Задание\s*\d+/', $line) && !preg_match('/Блок\s*\d+/', $line)) {
                $instruction .= ' ' . $line;
            } else {
                break;
            }
        }

        return $this->cleanText(trim($instruction));
    }

    /**
     * Detect zadanie type based on instruction and content
     */
    protected function detectZadanieType(string $instruction, string $content): string
    {
        $instructionLower = mb_strtolower($instruction);
        $contentLower = mb_strtolower($content);

        // Coordinate line related
        if (strpos($instructionLower, 'координатной прямой') !== false ||
            strpos($instructionLower, 'координатная прямая') !== false) {
            if (strpos($instructionLower, 'утвержден') !== false) {
                return 'choice';
            }
            if (strpos($instructionLower, 'точк') !== false) {
                return 'fraction_choice';
            }
        }

        // Interval/between questions
        if (strpos($instructionLower, 'между какими') !== false) {
            if (strpos($contentLower, '√') !== false || strpos($contentLower, 'sqrt') !== false) {
                return 'sqrt_interval';
            }
            return 'interval_choice';
        }

        // Промежуток (interval)
        if (strpos($instructionLower, 'промежутк') !== false) {
            return 'interval_choice';
        }

        // Отрезок (segment)
        if (strpos($instructionLower, 'отрезк') !== false) {
            return 'segment_choice';
        }

        // Root/sqrt questions
        if (strpos($contentLower, '√') !== false) {
            return 'sqrt_choice';
        }

        // Expression calculations
        if (strpos($instructionLower, 'найдите значение') !== false ||
            strpos($instructionLower, 'вычислите') !== false) {
            return 'expression';
        }

        // Comparison
        if (strpos($instructionLower, 'сравните') !== false) {
            return 'comparison';
        }

        // Which statement is true/false
        if (strpos($instructionLower, 'утвержден') !== false) {
            if (strpos($instructionLower, 'невер') !== false) {
                return 'false_statements';
            }
            return 'choice';
        }

        // Order/arrange
        if (strpos($instructionLower, 'расположите') !== false ||
            strpos($instructionLower, 'порядк') !== false) {
            return 'ordering';
        }

        // Default
        return 'choice';
    }

    /**
     * Parse individual tasks from zadanie text
     *
     * The PDF format for topic 07 is:
     *                                               1) opt1        3) opt3
     *  1                                            (task number on its own line)
     *                                               2) opt2        4) opt4
     *
     * Options are split across lines: 1,3 on top, task number, 2,4 below
     */
    protected function parseTasks(string $text, string $type, string $topicId, int $startImageIndex, int $imageCount): array
    {
        $tasks = [];
        $lines = explode("\n", $text);
        $totalLines = count($lines);

        // Find task number positions (standalone digit 1-9)
        $taskPositions = [];
        foreach ($lines as $idx => $line) {
            $trimmed = trim($line);
            // Check for standalone task number at start of line
            if (preg_match('/^\s*(\d)\s*$/u', $trimmed, $matches) ||
                preg_match('/^\s*(\d)\s+/u', $line, $matches)) {
                $num = (int)$matches[1];
                if ($num >= 1 && $num <= 9) {
                    $taskPositions[] = [
                        'number' => $num,
                        'line' => $idx,
                    ];
                }
            }
        }

        if (empty($taskPositions)) {
            // No task positions found, try expression list parsing
            return $this->parseExpressionList($text, $type);
        }

        $imageIndex = $startImageIndex;

        foreach ($taskPositions as $i => $taskPos) {
            $taskNum = $taskPos['number'];
            $taskLine = $taskPos['line'];

            // Get lines around task number (one above and one below typically contain options)
            $optionsBuffer = [];

            // Line above task number (contains options 1 and 3)
            if ($taskLine > 0) {
                $lineAbove = $lines[$taskLine - 1] ?? '';
                if (preg_match('/[1-4]\)/', $lineAbove)) {
                    $optionsBuffer[] = $lineAbove;
                }
            }

            // Line below task number (contains options 2 and 4)
            if ($taskLine < $totalLines - 1) {
                $lineBelow = $lines[$taskLine + 1] ?? '';
                if (preg_match('/[1-4]\)/', $lineBelow)) {
                    $optionsBuffer[] = $lineBelow;
                }
            }

            $parsedOptions = $this->parseOptionsFromBuffer($optionsBuffer);

            if (!empty($parsedOptions)) {
                $task = [
                    'id' => $taskNum,
                    'options' => $parsedOptions,
                ];

                // Add image if available
                if ($imageIndex < $startImageIndex + $imageCount) {
                    $task['image'] = sprintf('img-%03d.png', $imageIndex);
                    $imageIndex++;
                }

                $tasks[] = $task;
            }
        }

        // If no tasks with options found, try expression list
        if (empty($tasks)) {
            $tasks = $this->parseExpressionList($text, $type);
        }

        return $tasks;
    }

    /**
     * Parse options from collected buffer lines
     * Handles multi-column format: "1) opt1  3) opt3" / "2) opt2  4) opt4"
     */
    protected function parseOptionsFromBuffer(array $buffer): array
    {
        $optionsRaw = [];

        foreach ($buffer as $line) {
            // Find all option patterns in line: "N) content"
            // Match option number, then content until next option or end of line
            if (preg_match_all('/([1-4])\)\s*([^1-4\n]+?)(?=\s+[1-4]\)|$)/u', $line, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $num = (int)$match[1];
                    $opt = trim($match[2]);
                    if (!empty($opt) && !isset($optionsRaw[$num])) {
                        $optionsRaw[$num] = $this->convertToLatex($opt);
                    }
                }
            }
        }

        // Sort by option number
        ksort($optionsRaw);

        return array_values($optionsRaw);
    }

    /**
     * Parse simple options from text
     * Handles both inline format (1) a 2) b 3) c 4) d) and multiline with columns
     */
    protected function parseSimpleOptions(string $text): array
    {
        $options = [];
        $optionsRaw = [];

        // Look for all options in text (may be on multiple lines, in columns)
        // Pattern: number followed by ) and then content
        if (preg_match_all('/([1-4])\)\s*([^\n]+?)(?=\s*[1-4]\)|$)/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $num = (int)$match[1];
                $opt = trim($match[2]);
                // Clean trailing numbers that might be start of next option
                $opt = preg_replace('/\s+[1-4]$/', '', $opt);
                if (!empty($opt)) {
                    $optionsRaw[$num] = $this->convertToLatex($opt);
                }
            }
        }

        // Sort by option number and return as array
        ksort($optionsRaw);
        $options = array_values($optionsRaw);

        return $options;
    }

    /**
     * Parse expression list (for calculation tasks)
     */
    protected function parseExpressionList(string $text, string $type): array
    {
        $tasks = [];
        $lines = explode("\n", $text);

        // Skip first line (instruction)
        $taskNum = 1;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'Задание') !== false) continue;

            // Look for expressions with task numbers
            if (preg_match('/^(\d+)\)\s*(.+?)[;.]?\s*$/u', $line, $matches)) {
                $expression = $this->convertToLatex(trim($matches[2]));
                if (!empty($expression)) {
                    $tasks[] = [
                        'id' => (int)$matches[1],
                        'expression' => $expression,
                    ];
                }
            }
        }

        return $tasks;
    }

    /**
     * Convert text math expressions to LaTeX
     */
    protected function convertToLatex(string $text): string
    {
        // Clean up text
        $text = $this->cleanText($text);

        // Convert fractions (patterns like "3/4" or "numerator over denominator")
        // Simple inline fractions: 3/4 -> \frac{3}{4}
        $text = preg_replace('/(\d+)\s*\/\s*(\d+)/', '\\frac{$1}{$2}', $text);

        // Convert square roots: √n -> \sqrt{n}
        $text = preg_replace('/√\s*(\d+)/', '\\sqrt{$1}', $text);
        $text = preg_replace('/sqrt\s*\(?\s*(\d+)\s*\)?/i', '\\sqrt{$1}', $text);

        // Convert powers: n^2, n^3, etc.
        $text = preg_replace('/(\w)\s*\^\s*(\d+)/', '$1^{$2}', $text);

        // Convert multiplication dot
        $text = str_replace(['·', '×'], ' \\cdot ', $text);

        // Convert comparison operators
        $text = str_replace(['<=', '≤'], ' \\leq ', $text);
        $text = str_replace(['>=', '≥'], ' \\geq ', $text);
        $text = str_replace(['!=', '≠'], ' \\neq ', $text);

        // Convert decimals with comma to proper format
        $text = preg_replace('/(\d+),(\d+)/', '$1{,}$2', $text);

        // Clean up extra spaces
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Clean text from PDF artifacts
     */
    protected function cleanText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Remove page headers/footers
        $text = preg_replace('/Е\.\s*А\.\s*Ширяева.*?тренажер\)/ui', '', $text);

        // Remove form feeds
        $text = str_replace("\f", ' ', $text);

        return trim($text);
    }

    /**
     * Get basic parser instance
     */
    public function getBasicParser(): PdfParserService
    {
        return $this->basicParser;
    }
}
