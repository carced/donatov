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
        return is_file($root . '/storage/install.lock')
            && is_file($root . '/.env');
    }
}
