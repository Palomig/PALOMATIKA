<?php

namespace App\Console\Commands;

use App\Services\PdfParserService;
use Illuminate\Console\Command;

/**
 * Artisan command for parsing PDF task files
 *
 * Usage:
 *   php artisan pdf:parse task_07.pdf --topic=07
 *   php artisan pdf:parse task_07.pdf --text-only
 *   php artisan pdf:parse task_07.pdf --images-only --topic=07
 *   php artisan pdf:parse task_07.pdf --analyze
 */
class ParsePdfCommand extends Command
{
    protected $signature = 'pdf:parse
                            {filename : PDF filename in storage/app/pdf/}
                            {--topic= : Topic ID for image output directory (e.g., 07)}
                            {--text-only : Only extract text}
                            {--images-only : Only extract images}
                            {--analyze : Analyze PDF structure (blocks, zadaniya, images)}
                            {--page= : Specific page number to extract}
                            {--output= : Output file for text (default: stdout)}';

    protected $description = 'Parse PDF task files - extract text and images';

    protected PdfParserService $parser;

    public function __construct(PdfParserService $parser)
    {
        parent::__construct();
        $this->parser = $parser;
    }

    public function handle(): int
    {
        $filename = $this->argument('filename');
        $topic = $this->option('topic');

        // Check dependencies
        $deps = $this->parser->checkDependencies();
        if (!$deps['ok']) {
            $this->error('Missing dependencies: ' . implode(', ', $deps['missing']));
            $this->line('Install with: ' . $deps['install_hint']);
            return 1;
        }

        // Verify file exists
        $fullPath = $this->parser->getStoragePath() . '/' . $filename;
        if (!file_exists($fullPath)) {
            $this->error("PDF file not found: {$fullPath}");
            $this->line('Available PDFs:');
            $pdfs = glob($this->parser->getStoragePath() . '/*.pdf');
            foreach ($pdfs as $pdf) {
                $this->line('  - ' . basename($pdf));
            }
            return 1;
        }

        $this->info("Parsing: {$filename}");

        // Analyze mode
        if ($this->option('analyze')) {
            return $this->analyzeFile($filename);
        }

        // Text extraction
        if (!$this->option('images-only')) {
            $this->extractText($filename);
        }

        // Image extraction
        if (!$this->option('text-only')) {
            if (!$topic) {
                $this->warn('No --topic specified, skipping image extraction');
                $this->line('Use --topic=XX to extract images to public/images/tasks/XX/');
            } else {
                $this->extractImages($filename, $topic);
            }
        }

        return 0;
    }

    protected function extractText(string $filename): void
    {
        $this->info('Extracting text...');

        $options = [];
        if ($page = $this->option('page')) {
            $options['firstPage'] = $page;
            $options['lastPage'] = $page;
        }

        $text = $this->parser->extractText($filename, $options);

        if ($output = $this->option('output')) {
            file_put_contents($output, $text);
            $this->info("Text saved to: {$output}");
        } else {
            $this->line("\n--- TEXT CONTENT ---\n");
            $this->line($text);
            $this->line("\n--- END TEXT ---\n");
        }

        $lines = count(explode("\n", $text));
        $this->info("Extracted {$lines} lines of text");
    }

    protected function extractImages(string $filename, string $topic): void
    {
        $this->info("Extracting images to public/images/tasks/{$topic}/...");

        $images = $this->parser->extractImages($filename, $topic);

        $this->info("Extracted " . count($images) . " images:");

        $this->table(
            ['Filename', 'Web Path'],
            array_map(fn($img) => [$img['filename'], $img['path']], array_slice($images, 0, 20))
        );

        if (count($images) > 20) {
            $this->line('... and ' . (count($images) - 20) . ' more');
        }
    }

    protected function analyzeFile(string $filename): int
    {
        $this->info('Analyzing PDF structure...');

        $analysis = $this->parser->suggestImageMapping($filename);

        // Summary
        $this->newLine();
        $this->info("=== PDF ANALYSIS ===");
        $this->line("Total images: {$analysis['total_images']}");
        $this->line("Total pages with images: {$analysis['total_pages']}");

        // Blocks
        $this->newLine();
        $this->info("Blocks found:");
        if (empty($analysis['blocks'])) {
            $this->line("  (none)");
        } else {
            foreach ($analysis['blocks'] as $block) {
                $this->line("  Block {$block['number']}: {$block['title']} (line {$block['line']})");
            }
        }

        // Zadaniya
        $this->newLine();
        $this->info("Zadaniya found:");
        if (empty($analysis['zadaniya'])) {
            $this->line("  (none)");
        } else {
            foreach (array_slice($analysis['zadaniya'], 0, 10) as $z) {
                $instruction = mb_substr($z['instruction'], 0, 60);
                $this->line("  Zadanie {$z['number']}: {$instruction}...");
            }
            if (count($analysis['zadaniya']) > 10) {
                $this->line("  ... and " . (count($analysis['zadaniya']) - 10) . " more");
            }
        }

        // Images by page
        $this->newLine();
        $this->info("Images by page:");
        foreach ($analysis['images_by_page'] as $page => $images) {
            $count = count($images);
            $this->line("  Page {$page}: {$count} images");
        }

        // Image list
        $this->newLine();
        $this->info("Image list (first 30):");
        $imageList = $this->parser->listImages($filename);
        $this->table(
            ['#', 'Page', 'Type', 'Width', 'Height'],
            array_map(fn($img) => [
                $img['num'],
                $img['page'],
                $img['type'],
                $img['width'],
                $img['height'],
            ], array_slice($imageList, 0, 30))
        );

        return 0;
    }
}
