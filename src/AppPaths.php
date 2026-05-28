<?php

declare(strict_types=1);

namespace App;

/** Resolve project root for standard (public/) and flat (public_html) layouts. */
final class AppPaths
{
    public static function rootFromPublicEntry(string $publicDir): string
    {
        $publicDir = rtrim($publicDir, '/');
        if (is_file($publicDir . '/src/Config.php')) {
            return $publicDir;
        }

        $parent = dirname($publicDir);
        if (is_file($parent . '/src/Config.php')) {
            return $parent;
        }

        throw new \RuntimeException('Cannot find application root (src/Config.php).');
    }

    public static function isInstalled(string $root): bool
    {
        if (!is_file($root . '/.env')) {
            return false;
        }

        if (is_file($root . '/storage/install.lock')) {
            return true;
        }

        // Local dev: existing .env with APP_ENV=local skips installer redirect.
        return self::readEnvValue($root, 'APP_ENV') === 'local';
    }

    private static function readEnvValue(string $root, string $key): ?string
    {
        $path = $root . '/.env';
        if (!is_readable($path)) {
            return null;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_starts_with($line, $key . '=')) {
                continue;
            }

            return trim($line, $key . " =\t\"'");
        }

        return null;
    }
}
