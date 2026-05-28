<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

final class Installer
{
    private static string $root = '';

    public static function boot(string $publicOrRootDir): void
    {
        self::$root = AppPaths::rootFromPublicEntry($publicOrRootDir);
    }

    public static function appRoot(): string
    {
        if (self::$root === '') {
            self::boot(dirname(__DIR__) . '/public');
        }
        return self::$root;
    }

    public static function requirements(): array
    {
        $checks = [
            'php' => [
                'ok' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'label' => 'PHP 8.1+',
                'got' => PHP_VERSION,
            ],
            'pdo' => [
                'ok' => extension_loaded('pdo'),
                'label' => 'PDO',
                'got' => extension_loaded('pdo') ? 'yes' : 'no',
            ],
            'pdo_mysql' => [
                'ok' => extension_loaded('pdo_mysql'),
                'label' => 'PDO MySQL',
                'got' => extension_loaded('pdo_mysql') ? 'yes' : 'no',
            ],
            'json' => [
                'ok' => extension_loaded('json'),
                'label' => 'JSON',
                'got' => extension_loaded('json') ? 'yes' : 'no',
            ],
            'writable_storage' => [
                'ok' => false,
                'label' => 'storage/ writable',
                'got' => '',
            ],
            'writable_env' => [
                'ok' => false,
                'label' => '.env writable (or creatable)',
                'got' => '',
            ],
        ];

        $root = self::appRoot();
        $storage = $root . '/storage';
        if (!is_dir($storage)) {
            @mkdir($storage, 0755, true);
        }
        $checks['writable_storage']['ok'] = is_dir($storage) && is_writable($storage);
        $checks['writable_storage']['got'] = is_writable($storage) ? 'yes' : 'no';

        $envPath = $root . '/.env';
        if (is_file($envPath)) {
            $checks['writable_env']['ok'] = is_writable($envPath);
            $checks['writable_env']['got'] = is_writable($envPath) ? 'yes' : 'no';
        } else {
            $checks['writable_env']['ok'] = is_writable($root);
            $checks['writable_env']['got'] = is_writable($root) ? 'yes' : 'yes (create)';
        }

        return $checks;
    }

    public static function allRequirementsMet(): bool
    {
        foreach (self::requirements() as $c) {
            if (!$c['ok']) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, string> $input */
    public static function install(array $input): array
    {
        if (AppPaths::isInstalled(self::appRoot())) {
            return ['ok' => false, 'error' => 'Already installed. Delete storage/install.lock to reinstall.'];
        }

        $dbHost = trim($input['db_host'] ?? '127.0.0.1');
        $dbPort = trim($input['db_port'] ?? '3306');
        $dbName = trim($input['db_name'] ?? '');
        $dbUser = trim($input['db_user'] ?? '');
        $dbPass = (string) ($input['db_pass'] ?? '');
        $appUrl = rtrim(trim($input['app_url'] ?? ''), '/');
        $adminPass = (string) ($input['admin_password'] ?? '');
        $importCatalog = ($input['import_catalog'] ?? '') === '1';

        if ($dbName === '' || $dbUser === '' || $appUrl === '' || $adminPass === '') {
            return ['ok' => false, 'error' => 'Database name, user, site URL, and admin password are required.'];
        }

        if (!filter_var($appUrl, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'error' => 'Site URL must be valid (include https://).'];
        }

        try {
            $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbHost, $dbPort);
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . str_replace('`', '``', $dbName) . '`');
        } catch (PDOException $e) {
            return ['ok' => false, 'error' => 'Database connection failed: ' . $e->getMessage()];
        }

        self::runSqlFile($pdo, self::appRoot() . '/database/schema.sql');

        $migrations = [
            '/database/referral_migration.sql',
            '/database/referral_anonymous_migration.sql',
        ];
        foreach ($migrations as $file) {
            $path = self::appRoot() . $file;
            if (is_file($path)) {
                self::runSqlFile($pdo, $path);
            }
        }

        $env = self::buildEnv($dbHost, $dbPort, $dbName, $dbUser, $dbPass, $appUrl, $adminPass);
        if (file_put_contents(self::appRoot() . '/.env', $env) === false) {
            return ['ok' => false, 'error' => 'Could not write .env file. Check folder permissions.'];
        }

        $pdo->prepare('UPDATE settings SET `value` = ? WHERE `key` = ?')->execute(['GameWiwi.com', 'site_name']);

        $importMsg = '';
        if ($importCatalog && is_file(self::appRoot() . '/data/catalog.json')) {
            $importMsg = self::runCatalogImport();
        }

        if (!is_dir(self::appRoot() . '/storage')) {
            mkdir(self::appRoot() . '/storage', 0755, true);
        }
        file_put_contents(
            self::appRoot() . '/storage/install.lock',
            date('c') . "\nGameWiwi.com\n",
        );

        return [
            'ok' => true,
            'import' => $importMsg,
            'admin_password' => $adminPass,
        ];
    }

    private static function buildEnv(
        string $host,
        string $port,
        string $name,
        string $user,
        string $pass,
        string $appUrl,
        string $adminPass,
    ): string {
        $esc = static fn (string $v): string => str_replace(["\n", "\r"], '', $v);

        return implode("\n", [
            'APP_NAME=GameWiwi.com',
            'APP_URL=' . $esc($appUrl),
            'APP_ENV=production',
            'APP_DEBUG=false',
            '',
            'DB_HOST=' . $esc($host),
            'DB_PORT=' . $esc($port),
            'DB_DATABASE=' . $esc($name),
            'DB_USERNAME=' . $esc($user),
            'DB_PASSWORD=' . $esc($pass),
            '',
            'DISCOUNT_FACTOR=0.6',
            'DEFAULT_LANG=ru',
            '',
            'TRANSLATE_HTML=false',
            'AUTO_TRANSLATE_ON_READ=false',
            'TRANSLATION_PROVIDER=none',
            'TRANSLATION_API_KEY=',
            'LIBRETRANSLATE_URL=https://libretranslate.com',
            '',
            'ADMIN_PASSWORD=' . $esc($adminPass),
            '',
        ]) . "\n";
    }

    private static function runSqlFile(PDO $pdo, string $path): void
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new \RuntimeException('Missing SQL: ' . $path);
        }
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                if (!str_contains($e->getMessage(), 'Duplicate') && !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
        }
    }

    private static function runCatalogImport(): string
    {
        $importScript = self::appRoot() . '/import/import_to_mysql.php';
        if (!is_file($importScript)) {
            return 'Catalog import skipped (script missing).';
        }
        ob_start();
        try {
            passthru(PHP_BINARY . ' ' . escapeshellarg($importScript) . ' 2>&1', $code);
        } catch (\Throwable $e) {
            ob_end_clean();

            return 'Catalog import failed: ' . $e->getMessage();
        }
        $out = ob_get_clean();

        return $code === 0
            ? 'Catalog imported successfully.'
            : 'Catalog import finished with warnings. Run import manually if needed.';
    }
}
