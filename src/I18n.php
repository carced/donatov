<?php

declare(strict_types=1);

namespace App;

use PDO;

final class I18n
{
    private static string $lang = 'ru';
    private static array $strings = [];
    private static ?PDO $pdo = null;

    public static function init(PDO $pdo, string $lang): void
    {
        self::$pdo = $pdo;
        self::$lang = in_array($lang, ['ru', 'en'], true) ? $lang : 'ru';
        $file = dirname(__DIR__) . '/lang/' . self::$lang . '.json';
        self::$strings = is_file($file) ? json_decode(file_get_contents($file), true) : [];
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
        $stmt = self::$pdo->prepare(
            'SELECT id, text_value FROM translations
             WHERE entity_type = ? AND entity_id = ? AND field_name = ? AND lang = ?'
        );
        $stmt->execute([$type, $entityId, $field, self::$lang]);
        $row = $stmt->fetch();
        if ($row) {
            $existing = $row['text_value'] ?? '';
            if (trim($existing) !== '' && trim($existing) !== trim($ruFallback)) {
                return $existing;
            }
            // If the stored translation is empty or equals the RU fallback,
            // treat it as missing and try auto-translation on demand.
        }

        // Auto-translate missing EN strings on demand (cached into DB).
        $provider = Config::get('TRANSLATION_PROVIDER', 'none') ?? 'none';
        if ($provider === 'none' || trim($ruFallback) === '') {
            return $ruFallback;
        }

        // Avoid translating huge HTML blobs unless explicitly enabled.
        $isHtml = str_contains($ruFallback, '<') && str_contains($ruFallback, '>');
        $allowHtml = (Config::get('TRANSLATE_HTML', 'false') ?? 'false') === 'true';
        if ($isHtml && !$allowHtml) {
            return $ruFallback;
        }

        if (strlen($ruFallback) > 8000) {
            return $ruFallback;
        }

        try {
            $translated = Translator::translate($ruFallback, 'ru', self::$lang);
        } catch (\Throwable $e) {
            return $ruFallback;
        }

        if (trim($translated) === '' || $translated === $ruFallback) {
            return $ruFallback;
        }

        $ins = self::$pdo->prepare(
            'INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value, source_hash)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE text_value = VALUES(text_value), source_hash = VALUES(source_hash)'
        );
        $ins->execute([$type, $entityId, $field, self::$lang, $translated, $hash]);

        return $translated;
    }
}
