<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Курируемая учебная группировка задач ФИПИ по стабильным GUID.
 */
final class FipiTaskTaxonomy
{
    /** @param array<string, mixed> $manifest */
    public function __construct(private readonly array $manifest)
    {
    }

    public static function forTopic(string $topic): ?self
    {
        $path = resource_path("task-taxonomies/oge-topic-{$topic}.php");

        return is_file($path) ? new self(require $path) : null;
    }

    /** @return array<string, mixed> */
    public function manifest(): array
    {
        return $this->manifest;
    }

    public function validateManifest(): void
    {
        $topic = (string) ($this->manifest['topic'] ?? '');
        $expectedTotal = (int) ($this->manifest['expected_tasks'] ?? 0);
        $seenBlocks = [];
        $seenGroups = [];
        $seenGuids = [];
        $nextGroup = 1;

        foreach ($this->manifest['blocks'] ?? [] as $block) {
            $blockNumber = (int) ($block['number'] ?? 0);
            $blockTitle = trim((string) ($block['title'] ?? ''));

            if ($blockNumber < 1 || isset($seenBlocks[$blockNumber]) || $blockTitle === '') {
                throw new InvalidArgumentException(
                    "Тема {$topic}: некорректный раздел {$blockNumber}"
                );
            }
            $seenBlocks[$blockNumber] = true;

            foreach ($block['groups'] ?? [] as $group) {
                $number = (int) ($group['number'] ?? 0);
                $key = trim((string) ($group['key'] ?? ''));
                $title = trim((string) ($group['title'] ?? ''));

                if (
                    $number !== $nextGroup
                    || isset($seenGroups[$number])
                    || $key === ''
                    || $title === ''
                ) {
                    throw new InvalidArgumentException(
                        "Тема {$topic}: некорректная группа {$number}"
                    );
                }
                $seenGroups[$number] = true;
                $nextGroup++;

                $guids = array_values($group['guids'] ?? []);
                $expectedGroup = (int) ($group['expected_tasks'] ?? 0);
                if (count($guids) !== $expectedGroup) {
                    throw new InvalidArgumentException(
                        "Тема {$topic}: группа {$key} содержит " . count($guids)
                        . " GUID вместо {$expectedGroup}"
                    );
                }

                foreach ($guids as $guid) {
                    if (!is_string($guid) || $guid === '' || isset($seenGuids[$guid])) {
                        throw new InvalidArgumentException(
                            "Тема {$topic}: GUID {$guid} повторяется"
                        );
                    }
                    $seenGuids[$guid] = true;
                }
            }
        }

        if (count($seenGuids) !== $expectedTotal) {
            throw new InvalidArgumentException(
                "Тема {$topic}: карта содержит " . count($seenGuids)
                . " GUID вместо {$expectedTotal}"
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $tasks
     * @return array<int, array<string, mixed>>
     */
    public function group(array $tasks): array
    {
        $this->validateManifest();

        $topic = (string) ($this->manifest['topic'] ?? '');
        $expectedTotal = (int) ($this->manifest['expected_tasks'] ?? 0);
        $mapped = [];
        $definitions = [];

        foreach ($this->manifest['blocks'] ?? [] as $block) {
            foreach ($block['groups'] ?? [] as $group) {
                $key = (string) ($group['key'] ?? '');
                $guids = array_values($group['guids'] ?? []);
                $expectedGroup = (int) ($group['expected_tasks'] ?? 0);

                if (count($guids) !== $expectedGroup) {
                    throw new InvalidArgumentException(
                        "Тема {$topic}: группа {$key} содержит " . count($guids)
                        . " GUID вместо {$expectedGroup}"
                    );
                }

                foreach ($guids as $guid) {
                    if (isset($mapped[$guid])) {
                        throw new InvalidArgumentException(
                            "Тема {$topic}: GUID {$guid} повторяется в группах {$mapped[$guid]} и {$key}"
                        );
                    }
                    $mapped[$guid] = $key;
                }

                $definitions[] = [
                    'block_number' => (int) ($block['number'] ?? 0),
                    'block_title' => (string) ($block['title'] ?? ''),
                    'number' => (int) ($group['number'] ?? 0),
                    'key' => $key,
                    'title' => (string) ($group['title'] ?? ''),
                    'guids' => $guids,
                ];
            }
        }

        if (count($mapped) !== $expectedTotal) {
            throw new InvalidArgumentException(
                "Тема {$topic}: карта содержит " . count($mapped)
                . " GUID вместо {$expectedTotal}"
            );
        }

        $source = [];
        foreach ($tasks as $task) {
            $guid = (string) ($task['guid'] ?? '');
            if ($guid === '') {
                throw new InvalidArgumentException("Тема {$topic}: задача без GUID");
            }
            if (isset($source[$guid])) {
                throw new InvalidArgumentException("Тема {$topic}: GUID {$guid} повторяется в выгрузке");
            }
            $source[$guid] = $task;
        }

        $unclassified = array_values(array_diff(array_keys($source), array_keys($mapped)));
        if ($unclassified !== []) {
            throw new InvalidArgumentException(
                "Тема {$topic}: GUID {$unclassified[0]} отсутствует в учебной карте"
            );
        }

        $missing = array_values(array_diff(array_keys($mapped), array_keys($source)));
        if ($missing !== []) {
            throw new InvalidArgumentException(
                "Тема {$topic}: GUID {$missing[0]} из учебной карты отсутствует в выгрузке"
            );
        }

        foreach ($definitions as &$definition) {
            $definition['items'] = array_map(
                static fn (string $guid): array => $source[$guid],
                $definition['guids'],
            );
            usort(
                $definition['items'],
                static fn (array $a, array $b): int => ($a['order'] ?? []) <=> ($b['order'] ?? []),
            );
            unset($definition['guids']);
        }
        unset($definition);

        return $definitions;
    }
}
