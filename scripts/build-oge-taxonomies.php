<?php

declare(strict_types=1);

use App\Services\FipiTaskTaxonomy;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * @return list<string>
 */
function selectedTopics(array $arguments): array
{
    $value = '06-25';
    foreach ($arguments as $argument) {
        if (str_starts_with($argument, '--topics=')) {
            $value = substr($argument, strlen('--topics='));
        }
    }

    if (preg_match('/^(\d{1,2})-(\d{1,2})$/', $value, $matches) === 1) {
        $topics = [];
        foreach (range((int) $matches[1], (int) $matches[2]) as $number) {
            $topics[] = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
        }

        return $topics;
    }

    return array_map(
        static fn (string $topic): string => str_pad(trim($topic), 2, '0', STR_PAD_LEFT),
        explode(',', $value),
    );
}

function optionValue(array $arguments, string $name, string $default): string
{
    $prefix = "--{$name}=";
    foreach ($arguments as $argument) {
        if (str_starts_with($argument, $prefix)) {
            return substr($argument, strlen($prefix));
        }
    }

    return $default;
}

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

if ($argc < 3) {
    fail(
        'Использование: php scripts/build-oge-taxonomies.php '
        . '<bank.json> <curriculum.php> [--topics=06-25] [--output-dir=resources/task-taxonomies]'
    );
}

$bankPath = $argv[1];
$curriculumPath = $argv[2];
$arguments = array_slice($argv, 3);
$outputDir = optionValue(
    $arguments,
    'output-dir',
    dirname(__DIR__) . '/resources/task-taxonomies',
);

if (!is_file($bankPath)) {
    fail("Не найден банк: {$bankPath}");
}
if (!is_file($curriculumPath)) {
    fail("Не найден учебный план: {$curriculumPath}");
}

$bank = json_decode((string) file_get_contents($bankPath), true);
if (!is_array($bank) || !is_array($bank['tasks'] ?? null)) {
    fail("Некорректный банк: {$bankPath}");
}

$curriculum = require $curriculumPath;
if (!is_array($curriculum)) {
    fail("Некорректный учебный план: {$curriculumPath}");
}

$tasksByTopic = [];
foreach ($bank['tasks'] as $task) {
    $topicNumber = (int) ($task['topic'] ?? 0);
    if ($topicNumber < 6 || $topicNumber > 25) {
        continue;
    }
    $topic = str_pad((string) $topicNumber, 2, '0', STR_PAD_LEFT);
    $tasksByTopic[$topic][] = $task;
}

if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
    fail("Не удалось создать каталог: {$outputDir}");
}

$topicCount = 0;
$taskCount = 0;
foreach (selectedTopics($arguments) as $topic) {
    $items = $tasksByTopic[$topic] ?? [];
    if ($items === []) {
        fail("Тема {$topic} отсутствует в банке");
    }

    if ($topic === '16') {
        $existing = dirname(__DIR__) . '/resources/task-taxonomies/oge-topic-16.php';
        if (!is_file($existing)) {
            fail('Не найден утверждённый манифест темы 16');
        }
        $manifest = require $existing;
        (new FipiTaskTaxonomy($manifest))->group($items);
        $groups = array_merge(...array_column($manifest['blocks'], 'groups'));
        printf(
            "%s: разделов %d, групп %d, задач %d (утверждённая карта сохранена)\n",
            $topic,
            count($manifest['blocks']),
            count($groups),
            count($items),
        );
        $topicCount++;
        $taskCount += count($items);
        continue;
    }

    $topicCurriculum = $curriculum[$topic] ?? null;
    if (!is_array($topicCurriculum)) {
        fail("Тема {$topic} отсутствует в учебном плане");
    }

    $sourceSubtypes = [];
    foreach ($items as $task) {
        $subtype = (int) ($task['subtype_id'] ?? 0);
        $sourceSubtypes[$subtype][] = $task;
    }

    $usedSubtypes = [];
    $blocks = [];
    $groupNumber = 1;
    foreach (array_values($topicCurriculum['sections'] ?? []) as $blockIndex => $section) {
        $blockTitle = trim((string) ($section['title'] ?? ''));
        if ($blockTitle === '') {
            fail("Тема {$topic}: пустое название раздела");
        }

        $groups = [];
        foreach (array_values($section['groups'] ?? []) as $group) {
            $key = trim((string) ($group['key'] ?? ''));
            $title = trim((string) ($group['title'] ?? ''));
            $subtypes = array_map('intval', array_values($group['subtypes'] ?? []));
            if ($key === '' || $title === '' || $subtypes === []) {
                fail("Тема {$topic}: неполное описание группы {$groupNumber}");
            }

            $groupTasks = [];
            foreach ($subtypes as $subtype) {
                if (!isset($sourceSubtypes[$subtype])) {
                    fail("Тема {$topic}: подтип {$subtype} отсутствует в банке");
                }
                if (isset($usedSubtypes[$subtype])) {
                    fail("Тема {$topic}: подтип {$subtype} повторяется в учебном плане");
                }
                $usedSubtypes[$subtype] = true;
                array_push($groupTasks, ...$sourceSubtypes[$subtype]);
            }

            usort(
                $groupTasks,
                static fn (array $left, array $right): int =>
                    ($left['order'] ?? []) <=> ($right['order'] ?? []),
            );
            $groups[] = [
                'key' => $key,
                'number' => $groupNumber++,
                'title' => $title,
                'expected_tasks' => count($groupTasks),
                'guids' => array_column($groupTasks, 'guid'),
            ];
        }

        if ($groups === []) {
            fail("Тема {$topic}: раздел {$blockTitle} не содержит групп");
        }
        $blocks[] = [
            'number' => $blockIndex + 1,
            'title' => $blockTitle,
            'groups' => $groups,
        ];
    }

    foreach (array_keys($sourceSubtypes) as $subtype) {
        if (!isset($usedSubtypes[$subtype])) {
            fail("Тема {$topic}: подтип {$subtype} отсутствует в учебном плане");
        }
    }

    $manifest = [
        'topic' => $topic,
        'expected_tasks' => count($items),
        'blocks' => $blocks,
    ];
    (new FipiTaskTaxonomy($manifest))->group($items);

    $header = "<?php\n\n/**\n"
        . " * Курируемая учебная группировка темы {$topic} ОГЭ.\n"
        . " * Сгенерировано из актуального банка ФИПИ; порядок задаёт учебный план.\n"
        . " */\nreturn ";
    $path = "{$outputDir}/oge-topic-{$topic}.php";
    file_put_contents($path, $header . var_export($manifest, true) . ";\n");

    printf(
        "%s: разделов %d, групп %d, задач %d\n",
        $topic,
        count($blocks),
        $groupNumber - 1,
        count($items),
    );
    $topicCount++;
    $taskCount += count($items);
}

printf("ИТОГО: тем %d, задач %d\n", $topicCount, $taskCount);
