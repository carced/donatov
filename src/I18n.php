<?php

declare(strict_types=1);

namespace App;

use PDO;

final class I18n
{
    private static string $lang = 'ru';
    private static array $strings = [];
    private static ?PDO $pdo = null;
    private static array $entityCache = [];

    public static function init(PDO $pdo, string $lang): void
    {
        self::$pdo = $pdo;
        self::$lang = in_array($lang, ['ru', 'en'], true) ? $lang : 'ru';
        $file = dirname(__DIR__) . '/lang/' . self::$lang . '.json';
        self::$strings = is_file($file) ? json_decode(file_get_contents($file), true) : [];
        self::$entityCache = [];
    }

    public static function lang(): string
    {
        return self::$lang;
    }

    public static function t(string $key, array $replace = []): string
    {
        $text = self::$strings[$key] ?? $key;
        foreach ($replace as $k => $v) {
            $text = str_replace(':' . $k, (string) $v, $text);
        }
        return $text;
    }

    public static function transEntity(string $type, string $entityId, string $field, string $ruFallback): string
    {
        if (self::$lang === 'ru' || self::$pdo === null) {
            return $ruFallback;
        }

        $hash = hash('sha256', $ruFallback);
        $cacheKey = implode('|', [self::$lang, $type, $entityId, $field, $hash]);
        if (array_key_exists($cacheKey, self::$entityCache)) {
            return self::$entityCache[$cacheKey];
        }

        $stmt = self::$pdo->prepare(
            'SELECT id, text_value FROM translations
             WHERE entity_type = ? AND entity_id = ? AND field_name = ? AND lang = ?'
        );
        $stmt->execute([$type, $entityId, $field, self::$lang]);
        $row = $stmt->fetch();
        if ($row) {
            $existing = $row['text_value'] ?? '';
            if (trim($existing) !== '' && trim($existing) !== trim($ruFallback)) {
                self::$entityCache[$cacheKey] = $existing;
                return $existing;
            }
            // If the stored translation is empty or equals the RU fallback,
            // treat it as missing and try auto-translation on demand.
        }

        $provider = Config::get('TRANSLATION_PROVIDER', 'none') ?? 'none';
        $autoTranslate = (Config::get('AUTO_TRANSLATE_ON_READ', 'false') ?? 'false') === 'true';
        if (!$autoTranslate || $provider === 'none' || trim($ruFallback) === '') {
            self::$entityCache[$cacheKey] = $ruFallback;
            self::$entityCache[$cacheKey] = $ruFallback;
            return $ruFallback;
        }

        // Avoid translating huge HTML blobs unless explicitly enabled.
        $isHtml = str_contains($ruFallback, '<') && str_contains($ruFallback, '>');
        $allowHtml = (Config::get('TRANSLATE_HTML', 'false') ?? 'false') === 'true';
        if ($isHtml && !$allowHtml) {
            self::$entityCache[$cacheKey] = $ruFallback;
            return $ruFallback;
        }

        if (strlen($ruFallback) > 8000) {
            return $ruFallback;
        }

        try {
            $translated = Translator::translate($ruFallback, 'ru', self::$lang);
        } catch (\Throwable $e) {
            self::$entityCache[$cacheKey] = $ruFallback;
            return $ruFallback;
        }

        if (trim($translated) === '' || $translated === $ruFallback) {
            self::$entityCache[$cacheKey] = $ruFallback;
            return $ruFallback;
        }

        $ins = self::$pdo->prepare(
            'INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value, source_hash)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE text_value = VALUES(text_value), source_hash = VALUES(source_hash)'
        );
        $ins->execute([$type, $entityId, $field, self::$lang, $translated, $hash]);

        self::$entityCache[$cacheKey] = $translated;
        return $translated;
    }
}
