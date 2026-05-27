<?php

declare(strict_types=1);

namespace App;

final class Config
{
    private static ?array $env = null;

    public static function load(string $root): void
    {
        $file = $root . '/.env';
        if (!is_file($file)) {
            $file = $root . '/.env.example';
        }
        $lines = is_file($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        self::$env = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            self::$env[trim($k)] = trim($v, " \t\"'");
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $v = $_ENV[$key] ?? getenv($key);
        if ($v !== false && $v !== '') {
            return (string) $v;
        }
        return self::$env[$key] ?? $default;
    }

    public static function require(string $key): string
    {
        $v = self::get($key);
        if ($v === null || $v === '') {
            throw new \RuntimeException("Missing config: {$key}");
        }
        return $v;
    }
}
