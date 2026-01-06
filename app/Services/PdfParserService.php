<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * PDF Parser Service for OGE Task Books
 *
 * Extracts text and images from PDF files for task content.
 * Uses poppler-utils (pdftotext, pdfimages) for extraction.
 *
 * Usage:
 *   $parser = new PdfParserService();
 *
 *   // Extract text
 *   $text = $parser->extractText('task_07.pdf');
 *
 *   // Extract images
 *   $images = $parser->extractImages('task_07.pdf', '07');
 *
 *   // Get image info
 *   $info = $parser->listImages('task_07.pdf');
 */
class PdfParserService
{
    /**
     * Base path for PDF storage
     */
    protected string $storagePath;

    /**
     * Base path for public images
     */
    protected string $publicPath;

    public function __construct()
    {
        $this->storagePath = storage_path('app/pdf');
        $this->publicPath = public_path('images/tasks');
    }

    /**
     * Check if required tools are installed
     */
    public function checkDependencies(): array
    {
        $tools = [
            'pdftotext' => shell_exec('which pdftotext 2>/dev/null'),
            'pdfimages' => shell_exec('which pdfimages 2>/dev/null'),
        ];

        $missing = [];
        foreach ($tools as $tool => $path) {
            if (empty(trim($path ?? ''))) {
                $missing[] = $tool;
            }
        }

        return [
            'ok' => empty($missing),
            'missing' => $missing,
            'install_hint' => 'apt-get install poppler-utils',
        ];
    }

    /**
     * Extract text from PDF with layout preservation
     *
     * @param string $filename PDF filename (relative to storage/app/pdf/)
     * @param array $options Options: layout (bool), firstPage (int), lastPage (int)
     * @return string Extracted text
     */
    public function extractText(string $filename, array $options = []): string
    {
        $pdfPath = $this->storagePath . '/' . $filename;

        if (!file_exists($pdfPath)) {
            throw new \Exception("PDF file not found: {$pdfPath}");
        }

        $cmd = 'pdftotext';

        // Preserve layout (important for tasks formatting)
        if ($options['layout'] ?? true) {
            $cmd .= ' -layout';
        }

        // Page range
        if (isset($options['firstPage'])) {
            $cmd .= ' -f ' . (int)$options['firstPage'];
        }
        if (isset($options['lastPage'])) {
            $cmd .= ' -l ' . (int)$options['lastPage'];
        }

        $cmd .= ' ' . escapeshellarg($pdfPath) . ' -';

        $output = shell_exec($cmd);

        return $output ?? '';
    }

    /**
     * Get information about images in PDF
     *
     * @param string $filename PDF filename
     * @return array Image information with page, dimensions, etc.
     */
    public function listImages(string $filename): array
    {
        $pdfPath = $this->storagePath . '/' . $filename;

        if (!file_exists($pdfPath)) {
            throw new \Exception("PDF file not found: {$pdfPath}");
        }

        $output = shell_exec('pdfimages -list ' . escapeshellarg($pdfPath) . ' 2>&1');

        $lines = explode("\n", $output);
        $images = [];

        foreach ($lines as $line) {
            // Skip header lines
            if (strpos($line, 'page') === 0 || strpos($line, '---') === 0 || empty(trim($line))) {
                continue;
            }

            // Parse image info line
            // Format: page num type width height color comp bpc enc interp object ID x-ppi y-ppi size ratio
            $parts = preg_split('/\s+/', trim($line));

            if (count($parts) >= 5) {
                $images[] = [
                    'page' => (int)($parts[0] ?? 0),
                    'num' => (int)($parts[1] ?? 0),
                    'type' => $parts[2] ?? 'unknown',
                    'width' => (int)($parts[3] ?? 0),
                    'height' => (int)($parts[4] ?? 0),
                    'color' => $parts[5] ?? '',
                ];
            }
        }

        return $images;
    }

    /**
     * Extract images from PDF and save to public directory
     *
     * @param string $filename PDF filename
     * @param string $topicId Topic ID for directory naming (e.g., '07')
     * @param string $format Image format: 'png', 'jpg', 'ppm'
     * @return array Extracted image paths
     */
    public function extractImages(string $filename, string $topicId, string $format = 'png'): array
    {
        $pdfPath = $this->storagePath . '/' . $filename;

        if (!file_exists($pdfPath)) {
            throw new \Exception("PDF file not found: {$pdfPath}");
        }

        // Create output directory
        $outputDir = $this->publicPath . '/' . $topicId;
        if (!File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        // Also create temp directory in storage
        $tempDir = $this->storagePath . '/images/task_' . $topicId;
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Extract images
        $formatFlag = $format === 'png' ? '-png' : ($format === 'jpg' ? '-j' : '');
        $cmd = "pdfimages {$formatFlag} " . escapeshellarg($pdfPath) . " " . escapeshellarg($tempDir . '/img');

        shell_exec($cmd . ' 2>&1');

        // Get extracted files
        $files = glob($tempDir . '/img-*.' . ($format === 'jpg' ? 'jpg' : 'png'));

        // Copy to public directory
        $extractedImages = [];
        foreach ($files as $file) {
            $basename = basename($file);
            $destPath = $outputDir . '/' . $basename;

            copy($file, $destPath);

            $extractedImages[] = [
                'filename' => $basename,
                'path' => '/images/tasks/' . $topicId . '/' . $basename,
                'full_path' => $destPath,
            ];
        }

        return $extractedImages;
    }

    /**
     * Extract PDF from git history
     *
     * @param string $commit Git commit hash
     * @param string $gitPath Path in git (e.g., 'РиР_ ОГЭ (тренажер)/ОГЭ 2026 Задание №07.pdf')
     * @param string $outputFilename Output filename
     * @return bool Success
     */
    public function extractFromGit(string $commit, string $gitPath, string $outputFilename): bool
    {
        $outputPath = $this->storagePath . '/' . $outputFilename;

        // Ensure directory exists
        $dir = dirname($outputPath);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $cmd = sprintf(
            'git show "%s:%s" > %s 2>&1',
            $commit,
            $gitPath,
            escapeshellarg($outputPath)
        );

        $result = shell_exec($cmd);

        return file_exists($outputPath) && filesize($outputPath) > 0;
    }

    /**
     * Get text split by pages
     *
     * @param string $filename PDF filename
     * @return array Array of page texts
     */
    public function getTextByPages(string $filename): array
    {
        $fullText = $this->extractText($filename);

        // Split by form feed character (page break)
        $pages = preg_split('/\f/', $fullText);

        return array_map('trim', $pages);
    }

    /**
     * Find blocks in text (e.g., "Блок 1. ФИПИ")
     *
     * @param string $text Full text
     * @return array Array of blocks with line numbers and titles
     */
    public function findBlocks(string $text): array
    {
        $lines = explode("\n", $text);
        $blocks = [];

        foreach ($lines as $lineNum => $line) {
            if (preg_match('/Блок\s*(\d+)\.\s*(.+)/u', $line, $matches)) {
                $blocks[] = [
                    'number' => (int)$matches[1],
                    'title' => trim($matches[2]),
                    'line' => $lineNum + 1,
                ];
            }
        }

        return $blocks;
    }

    /**
     * Find zadaniya (tasks) in text
     *
     * @param string $text Full text
     * @return array Array of zadaniya with numbers and instructions
     */
    public function findZadaniya(string $text): array
    {
        $lines = explode("\n", $text);
        $zadaniya = [];

        foreach ($lines as $lineNum => $line) {
            if (preg_match('/Задание\s*(\d+)\.\s*(.+)/u', $line, $matches)) {
                $zadaniya[] = [
                    'number' => (int)$matches[1],
                    'instruction' => trim($matches[2]),
                    'line' => $lineNum + 1,
                ];
            }
        }

        return $zadaniya;
    }

    /**
     * Generate image mapping suggestion based on page analysis
     *
     * @param string $filename PDF filename
     * @return array Suggested mapping of images to tasks
     */
    public function suggestImageMapping(string $filename): array
    {
        $images = $this->listImages($filename);
        $text = $this->extractText($filename);
        $blocks = $this->findBlocks($text);
        $zadaniya = $this->findZadaniya($text);

        // Group images by page
        $imagesByPage = [];
        foreach ($images as $img) {
            $page = $img['page'];
            if (!isset($imagesByPage[$page])) {
                $imagesByPage[$page] = [];
            }
            $imagesByPage[$page][] = $img;
        }

        return [
            'total_images' => count($images),
            'total_pages' => count($imagesByPage),
            'blocks' => $blocks,
            'zadaniya' => $zadaniya,
            'images_by_page' => $imagesByPage,
            'suggestion' => 'Review images_by_page to map images to zadaniya based on page numbers and order',
        ];
    }

    /**
     * Get storage path for PDFs
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * Get public path for images
     */
    public function getPublicPath(): string
    {
        return $this->publicPath;
    }
}
