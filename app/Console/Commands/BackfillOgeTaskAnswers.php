<?php

namespace App\Console\Commands;

use App\Services\TaskAnswerResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackfillOgeTaskAnswers extends Command
{
    protected $signature = 'oge:backfill-answers {--topic=* : Topic IDs, e.g. 06 07} {--dry-run : Do not write files}';

    protected $description = 'Backfill answer field for OGE JSON tasks in storage/app/tasks/topic_*.json';

    public function __construct(private readonly TaskAnswerResolver $answerResolver)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $topics = $this->option('topic');
        $dryRun = (bool) $this->option('dry-run');

        $files = empty($topics)
            ? glob(storage_path('app/tasks/topic_*.json'))
            : array_map(fn ($t) => storage_path('app/tasks/topic_' . str_pad((string) $t, 2, '0', STR_PAD_LEFT) . '.json'), $topics);

        if (empty($files)) {
            $this->warn('No topic files found.');
            return self::SUCCESS;
        }

        $totalTasks = 0;
        $resolved = 0;
        $unknown = 0;
        $updatedFiles = 0;

        foreach ($files as $path) {
            if (!File::exists($path)) {
                $this->warn('Missing file: ' . $path);
                continue;
            }

            $data = json_decode(File::get($path), true);
            if (!is_array($data)) {
                $this->warn('Invalid JSON: ' . $path);
                continue;
            }

            $changed = false;

            foreach ($data['blocks'] ?? [] as &$block) {
                foreach ($block['zadaniya'] ?? [] as &$zadanie) {
                    foreach ($zadanie['tasks'] ?? [] as &$task) {
                        $totalTasks++;
                        $answer = $this->answerResolver->resolveFromTaskAndZadanie($zadanie, $task);

                        if ($answer === null) {
                            $task['answer'] = TaskAnswerResolver::UNKNOWN_ANSWER;
                            $unknown++;
                        } else {
                            $task['answer'] = $answer;
                            $resolved++;
                        }

                        $changed = true;
                    }
                    unset($task);
                }
                unset($zadanie);
            }
            unset($block);

            if ($changed && !$dryRun) {
                File::put(
                    $path,
                    json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
                );
                $updatedFiles++;
            }
        }

        $this->info('Total tasks: ' . $totalTasks);
        $this->info('Resolved answers: ' . $resolved);
        $this->info('Unknown answers: ' . $unknown);
        $this->info(($dryRun ? 'Would update files: ' : 'Updated files: ') . $updatedFiles);

        return self::SUCCESS;
    }
}
