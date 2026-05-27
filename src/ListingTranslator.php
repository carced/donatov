<?php

declare(strict_types=1);

namespace App;

final class ListingTranslator
{
    /** @var array{exact: array<string, string>, replace: array<string, string>}|null */
    private static ?array $glossary = null;

    /** @var list<array{0: string, 1: string}>|null */
    private static ?array $replaceRules = null;

    /** @var array<string, string>|null */
    private static ?array $packDict = null;

    public static function toEnglish(string $text): string
    {
        if (I18n::lang() === 'ru' || trim($text) === '') {
            return $text;
        }

        self::loadGlossary();
        $trimmed = trim($text);

        if (isset(self::$glossary['exact'][$trimmed])) {
            return self::$glossary['exact'][$trimmed];
        }

        foreach (self::$glossary['exact'] as $ru => $en) {
            if (self::lower($trimmed) === self::lower($ru)) {
                return $en;
            }
        }

        $result = $text;
        foreach (self::$replaceRules ?? [] as [$ru, $en]) {
            $pattern = '/' . preg_quote($ru, '/') . '/iu';
            $result = preg_replace($pattern, $en, $result) ?? $result;
        }

        $dict = self::loadPackDict();
        if (isset($dict[$trimmed])) {
            return $dict[$trimmed];
        }
        if (isset($dict[$result])) {
            return $dict[$result];
        }

        if (self::hasCyrillic($result)) {
            $translated = Translator::translateFree($trimmed, 'ru', 'en');
            if ($translated !== '' && $translated !== $trimmed) {
                return $translated;
            }
        }

        return $result;
    }

    private static function loadPackDict(): array
    {
        if (self::$packDict === null) {
            $path = dirname(__DIR__) . '/lang/pack_names_en.json';
            $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
            self::$packDict = is_array($data) ? $data : [];
        }
        return self::$packDict;
    }

    private static function hasCyrillic(string $text): bool
    {
        return (bool) preg_match('/[А-Яа-яЁё]/u', $text);
    }

    private static function loadGlossary(): void
    {
        if (self::$glossary !== null) {
            return;
        }

        $path = dirname(__DIR__) . '/config/listing_en_glossary.php';
        $data = is_file($path) ? require $path : ['exact' => [], 'replace' => []];
        self::$glossary = [
            'exact' => $data['exact'] ?? [],
            'replace' => $data['replace'] ?? [],
        ];

        $replace = self::$glossary['replace'];
        uksort($replace, static fn (string $a, string $b): int =>
            self::len($b) <=> self::len($a));
        self::$replaceRules = [];
        foreach ($replace as $ru => $en) {
            self::$replaceRules[] = [$ru, $en];
        }
    }

    private static function lower(string $text): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($text, 'UTF-8');
        }

        return strtolower($text);
    }

    private static function len(string $text): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($text, 'UTF-8');
        }

        return strlen($text);
    }
}
