<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuditSemanticSvgAssetsCommand extends Command
{
    protected $signature = 'assets:audit-semantic-svg {--json : Output JSON summary} {--write : Write report to storage/app/reports}';

    protected $description = 'Audit task assets by modality (semantic SVG / text-KaTeX / raster reference) and flag risky runtime PNG usage';

    public function handle(): int
    {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'categories' => [
                'semantic-svg' => ['count' => 0, 'examples' => []],
                'text/katex' => ['count' => 0, 'examples' => []],
                'raster-reference' => ['count' => 0, 'examples' => []],
            ],
            'risky_runtime_png_usage' => [],
        ];

        foreach (File::glob(storage_path('app/tasks/topic_*.json')) as $path) {
            $topicId = preg_match('/topic_(\d+)\.json$/', (string) $path, $m) === 1 ? $m[1] : 'unknown';
            $data = json_decode((string) File::get($path), true);

            if (!is_array($data)) {
                continue;
            }

            foreach (($data['blocks'] ?? []) as $block) {
                foreach (($block['zadaniya'] ?? []) as $zadanie) {
                    $this->classifyNode($report, $topicId, $block, $zadanie, null);

                    foreach (($zadanie['tasks'] ?? []) as $task) {
                        $this->classifyNode($report, $topicId, $block, $zadanie, $task);
                    }
                }
            }
        }

        $this->scanRiskyRuntimePngUsage($report);

        if ($this->option('write')) {
            $this->writeReport($report);
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $this->renderHumanReport($report);

        return self::SUCCESS;
    }

    private function classifyNode(array &$report, string $topicId, array $block, array $zadanie, ?array $task): void
    {
        $node = $task ?? $zadanie;
        if (!is_array($node)) {
            return;
        }

        $ref = [
            'topic' => $topicId,
            'block' => $block['number'] ?? null,
            'zadanie' => $zadanie['number'] ?? null,
            'task' => $task['id'] ?? null,
        ];

        $hasSemanticSvg = $this->hasSemanticSvg($node);
        $hasRasterReference = $this->hasRasterReference($node);
        $hasTextOrKatex = $this->hasTextOrKatex($node, $zadanie);

        if ($hasSemanticSvg) {
            $this->bumpCategory($report['categories']['semantic-svg'], $ref);
            return;
        }

        if ($hasRasterReference) {
            $this->bumpCategory($report['categories']['raster-reference'], $ref);
            return;
        }

        if ($hasTextOrKatex) {
            $this->bumpCategory($report['categories']['text/katex'], $ref);
        }
    }

    private function hasSemanticSvg(array $node): bool
    {
        $svg = $node['svg'] ?? null;
        if (is_string($svg) && str_starts_with(ltrim($svg), '<svg')) {
            return true;
        }

        return !empty($node['svg_type']);
    }

    private function hasRasterReference(array $node): bool
    {
        $image = $node['image'] ?? null;
        if (!is_string($image) || trim($image) === '') {
            return false;
        }

        return !str_starts_with(ltrim($image), '<svg');
    }

    private function hasTextOrKatex(array $node, array $zadanie): bool
    {
        foreach (['expression', 'text', 'instruction'] as $key) {
            if (!empty($node[$key]) && is_string($node[$key])) {
                return true;
            }
        }

        if (!empty($node['options']) && is_array($node['options'])) {
            return true;
        }

        if (!empty($node['statements']) && is_array($node['statements'])) {
            return true;
        }

        return false;
    }

    private function bumpCategory(array &$bucket, array $ref): void
    {
        $bucket['count']++;

        if (count($bucket['examples']) < 8) {
            $bucket['examples'][] = $ref;
        }
    }

    private function scanRiskyRuntimePngUsage(array &$report): void
    {
        $paths = array_merge(
            File::glob(resource_path('views/**/*.blade.php')) ?: [],
            File::glob(app_path('**/*.php')) ?: []
        );

        // Fallback for environments where ** glob is not recursive.
        if ($paths === []) {
            $paths = [];
            foreach ([resource_path('views'), app_path()] as $root) {
                foreach (File::allFiles($root) as $file) {
                    $paths[] = $file->getPathname();
                }
            }
        }

        $seen = [];

        foreach ($paths as $path) {
            if (!File::exists($path)) {
                continue;
            }

            $lines = file($path, FILE_IGNORE_NEW_LINES);
            if (!is_array($lines)) {
                continue;
            }

            foreach ($lines as $lineNo => $line) {
                $trimmed = trim((string) $line);

                $isRisky = str_contains($trimmed, "asset('images/tasks/")
                    || str_contains($trimmed, 'asset("images/tasks/')
                    || (str_contains($trimmed, 'images/tasks/') && str_contains($trimmed, '.png'));

                if (!$isRisky) {
                    continue;
                }

                $key = $path . ':' . ($lineNo + 1);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $report['risky_runtime_png_usage'][] = [
                    'file' => str_replace(base_path() . '/', '', $path),
                    'line' => $lineNo + 1,
                    'snippet' => substr($trimmed, 0, 180),
                ];
            }
        }
    }

    private function writeReport(array $report): void
    {
        $dir = storage_path('app/reports');
        File::ensureDirectoryExists($dir);

        $path = $dir . '/semantic-svg-asset-audit-' . now()->format('Ymd_His') . '.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Report written: {$path}");
    }

    private function renderHumanReport(array $report): void
    {
        $this->info('Semantic SVG Asset Audit');
        $this->line('Generated at: ' . $report['generated_at']);
        $this->newLine();

        $rows = [];
        foreach ($report['categories'] as $category => $bucket) {
            $rows[] = [$category, $bucket['count']];
        }
        $this->table(['Category', 'Count'], $rows);

        $this->line('Risky runtime PNG usage: ' . count($report['risky_runtime_png_usage']));
        foreach (array_slice($report['risky_runtime_png_usage'], 0, 20) as $risk) {
            $this->line("- {$risk['file']}:{$risk['line']} {$risk['snippet']}");
        }

        if (count($report['risky_runtime_png_usage']) > 20) {
            $this->line('... truncated, use --json or --write for full list');
        }
    }
}
