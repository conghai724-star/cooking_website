<?php

declare(strict_types=1);

if (!function_exists('profanity_dictionary_paths')) {
    function profanity_dictionary_paths(): array
    {
        return [
            APPROOT . '/app/dictionaries/profanity_vi.json',
        ];
    }
}

if (!function_exists('profanity_load_words')) {
    function profanity_load_words(): array
    {
        static $cached = null;
        if (is_array($cached)) {
            return $cached;
        }

        $words = [];
        foreach (profanity_dictionary_paths() as $path) {
            if (!is_file($path)) {
                continue;
            }
            $raw = @file_get_contents($path);
            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }
            foreach ($decoded as $item) {
                if (!is_string($item)) {
                    continue;
                }
                $w = trim($item);
                if ($w !== '') {
                    $words[] = $w;
                }
            }
        }

        $words = array_values(array_unique($words));
        usort($words, static function (string $a, string $b): int {
            $lenA = function_exists('mb_strlen') ? mb_strlen($a, 'UTF-8') : strlen($a);
            $lenB = function_exists('mb_strlen') ? mb_strlen($b, 'UTF-8') : strlen($b);
            return $lenB <=> $lenA;
        });
        $cached = $words;
        return $cached;
    }
}

if (!function_exists('profanity_mask')) {
    function profanity_mask(string $text): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return $text;
        }

        $words = profanity_load_words();
        if ($words === []) {
            return $text;
        }

        $output = $text;
        foreach ($words as $word) {
            $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
            if (!is_array($chars) || $chars === []) {
                continue;
            }

            $escapedChars = array_map(static fn(string $ch): string => preg_quote($ch, '/'), $chars);
            $core = implode('[\\W_]*', $escapedChars);
            $pattern = '/(?<!\pL)(' . $core . ')(?!\pL)/ui';

            $output = preg_replace_callback(
                $pattern,
                static function (array $matches): string {
                    $matched = (string) ($matches[1] ?? '');
                    $len = function_exists('mb_strlen') ? mb_strlen($matched, 'UTF-8') : strlen($matched);
                    return str_repeat('*', max(1, $len));
                },
                $output
            ) ?? $output;
        }

        return $output;
    }
}
