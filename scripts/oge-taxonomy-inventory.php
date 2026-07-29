<?php

declare(strict_types=1);

function inventoryTopics(array $arguments): array
{
    $value = '06-25';
    foreach ($arguments as $argument) {
        if (str_starts_with($argument, '--topics=')) {
            $value = substr($argument, strlen('--topics='));
        }
    }

    if (preg_match('/^(\d{1,2})-(\d{1,2})$/', $value, $matches) === 1) {
        return range((int) $matches[1], (int) $matches[2]);
    }

    return array_map('intval', explode(',', $value));
}

if ($argc < 2 || !is_file($argv[1])) {
    fwrite(
        STDERR,
        "Использование: php scripts/oge-taxonomy-inventory.php <bank.json> [--topics=06-25]\n",
    );
    exit(1);
}

$bank = json_decode((string) file_get_contents($argv[1]), true);
if (!is_array($bank) || !is_array($bank['tasks'] ?? null)) {
    fwrite(STDERR, "Некорректный банк\n");
    exit(1);
}

$selected = array_fill_keys(inventoryTopics(array_slice($argv, 2)), true);
$groups = [];
foreach ($bank['tasks'] as $task) {
    $topic = (int) ($task['topic'] ?? 0);
    if (!isset($selected[$topic])) {
        continue;
    }
    $subtype = (int) ($task['subtype_id'] ?? 0);
    $groups[$topic][$subtype][] = $task;
}

echo "тема\tподтип\tзадач\tназвание ФИПИ\tпервый GUID\tпример условия\n";
ksort($groups);
foreach ($groups as $topic => $subtypes) {
    ksort($subtypes);
    foreach ($subtypes as $subtype => $tasks) {
        usort(
            $tasks,
            static fn (array $left, array $right): int =>
                ($left['order'] ?? []) <=> ($right['order'] ?? []),
        );
        $first = $tasks[0];
        $condition = html_entity_decode(
            strip_tags((string) ($first['html'] ?? '')),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );
        $condition = preg_replace('/\s+/u', ' ', trim($condition)) ?? '';
        if (mb_strlen($condition) > 140) {
            $condition = mb_substr($condition, 0, 137) . '…';
        }
        $title = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) ($first['subtype_title'] ?? '')),
        ) ?? '';

        printf(
            "%02d\t%d\t%d\t%s\t%s\t%s\n",
            $topic,
            $subtype,
            count($tasks),
            $title,
            (string) ($first['guid'] ?? ''),
            $condition,
        );
    }
}
