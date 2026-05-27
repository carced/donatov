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
            'SELECT text_value FROM translations
             WHERE entity_type = ? AND entity_id = ? AND field_name = ? AND lang = ?'
        );
        $stmt->execute([$type, $entityId, $field, self::$lang]);
        $row = $stmt->fetch();
        if ($row && ($row['text_value'] ?? '') !== '') {
            return $row['text_value'];
        }
        return $ruFallback;
    }
}
