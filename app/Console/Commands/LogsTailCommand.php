<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LogsTailCommand extends Command
{
    protected $signature = 'logs:tail {--lines=200 : Number of trailing lines to read}';
    protected $description = 'Return the tail of storage/logs/laravel.log';

    public function handle(): int
    {
        $lines = max(1, min(2000, (int) $this->option('lines')));
        $path = storage_path('logs/laravel.log');

        if (!is_file($path)) {
            $this->warn("Log file not found: {$path}");
            return 0;
        }

        $fp = fopen($path, 'r');
        if (!$fp) {
            $this->error("Cannot open log file");
            return 1;
        }

        $buffer = '';
        $chunk = 8192;
        $pos = -1;
        $newlines = 0;
        fseek($fp, 0, SEEK_END);
        $fileSize = ftell($fp);
        $read = 0;

        while ($read < $fileSize && $newlines <= $lines) {
            $readSize = min($chunk, $fileSize - $read);
            $read += $readSize;
            fseek($fp, $fileSize - $read);
            $data = fread($fp, $readSize);
            $buffer = $data . $buffer;
            $newlines = substr_count($buffer, "\n");
        }
        fclose($fp);

        $bufferLines = explode("\n", $buffer);
        $tail = array_slice($bufferLines, -$lines);
        $this->line(implode("\n", $tail));

        return 0;
    }
}
