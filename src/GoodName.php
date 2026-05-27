<?php

declare(strict_types=1);

namespace App;

final class GoodName
{
    /** Strip regional/platform suffixes from catalog titles. */
    public static function sanitize(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return $name;
        }

        $patterns = [
            '/\s*\(ПК\)\s*/iu',
            '/\s*\(Россия\/РБ\)\s*/iu',
            '/\s*ПК\s*$/iu',
        ];

        foreach ($patterns as $pattern) {
            $name = preg_replace($pattern, ' ', $name) ?? $name;
        }

        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    public static function display(array $good): string
    {
        $raw = I18n::transEntity('good', (string) $good['id'], 'name', $good['name_ru']);

        return self::sanitize($raw);
    }
}
