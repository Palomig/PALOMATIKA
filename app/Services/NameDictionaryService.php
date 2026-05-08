<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class NameDictionaryService
{
    private const CACHE_KEY = 'first_names_dict_v1';
    private const CACHE_TTL = 3600;
    private const PATH = 'data/first_names.json';

    /**
     * Проверяет, что first_name (после нормализации) есть в словаре.
     * Дефис/апостроф разделяют составные имена — каждая часть проверяется
     * отдельно. Возвращает true если ВСЕ части в словаре.
     */
    public function isKnownName(string $name): bool
    {
        $normalized = $this->normalize($name);
        if ($normalized === '') {
            return false;
        }

        $parts = preg_split('/[\-\']+/u', $normalized) ?: [$normalized];
        $dict = $this->dictionary();

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || mb_strlen($part) < 2) {
                return false;
            }
            if (!in_array($part, $dict, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Капитализирует первую букву каждой части (через дефис/апостроф/пробел).
     * "иван-петров" → "Иван-Петров", "о'брайен" → "О'Брайен".
     */
    public function capitalize(string $name): string
    {
        $name = mb_strtolower(trim($name));
        if ($name === '') {
            return '';
        }

        $parts = preg_split('/([\-\'\s]+)/u', $name, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = '';

        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                $out .= $part;
                continue;
            }
            if ($part === '') {
                continue;
            }
            $out .= mb_strtoupper(mb_substr($part, 0, 1)) . mb_substr($part, 1);
        }

        return $out;
    }

    private function normalize(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    /**
     * @return string[]
     */
    private function dictionary(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $path = storage_path('app/' . self::PATH);
            if (!is_file($path)) {
                return [];
            }
            $raw = file_get_contents($path);
            $json = json_decode($raw, true);
            $names = is_array($json['names'] ?? null) ? $json['names'] : [];

            return array_map(fn ($n) => mb_strtolower(trim((string) $n)), $names);
        });
    }
}
