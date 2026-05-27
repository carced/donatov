<?php

declare(strict_types=1);

namespace App;

final class Translator
{
    private static array $cache = [];

    public static function translate(string $text, string $from = 'ru', string $to = 'en'): string
    {
        $text = trim($text);
        if ($text === '' || $from === $to) {
            return $text;
        }
        $key = md5($from . '|' . $to . '|' . $text);
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $provider = Config::get('TRANSLATION_PROVIDER', 'none');
        $result = match ($provider) {
            'deepl' => self::deepl($text, $to),
            'google' => self::google($text, $to),
            'libretranslate' => self::libretranslate($text, $from, $to),
            default => self::fallbackTranslate($text),
        };
        self::$cache[$key] = $result;
        usleep(100000);
        return $result;
    }

    private static function fallbackTranslate(string $text): string
    {
        static $dict = null;
        if ($dict === null) {
            $path = dirname(__DIR__) . '/lang/dict_en.json';
            $dict = is_file($path) ? json_decode(file_get_contents($path), true) : [];
        }
        return $dict[$text] ?? $text;
    }

    private static function libretranslate(string $text, string $from, string $to): string
    {
        $url = rtrim(Config::get('LIBRETRANSLATE_URL', 'https://libretranslate.com'), '/') . '/translate';
        $payload = json_encode([
            'q' => $text,
            'source' => $from,
            'target' => $to,
            'format' => 'text',
        ]);
        $headers = ['Content-Type: application/json'];
        $key = Config::get('TRANSLATION_API_KEY');
        if ($key) {
            $headers[] = 'Authorization: Bearer ' . $key;
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $payload,
                'timeout' => 30,
            ],
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            return self::fallbackTranslate($text);
        }
        $data = json_decode($resp, true);
        return $data['translatedText'] ?? self::fallbackTranslate($text);
    }

    private static function deepl(string $text, string $to): string
    {
        $key = Config::get('TRANSLATION_API_KEY');
        if (!$key) {
            return self::fallbackTranslate($text);
        }
        $target = strtoupper($to === 'en' ? 'EN' : $to);
        $url = 'https://api-free.deepl.com/v2/translate';
        $post = http_build_query(['auth_key' => $key, 'text' => $text, 'target_lang' => $target]);
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $post]]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            return self::fallbackTranslate($text);
        }
        $data = json_decode($resp, true);
        return $data['translations'][0]['text'] ?? self::fallbackTranslate($text);
    }

    private static function google(string $text, string $to): string
    {
        $key = Config::get('TRANSLATION_API_KEY');
        if (!$key) {
            return self::fallbackTranslate($text);
        }
        $url = 'https://translation.googleapis.com/language/translate/v2?' . http_build_query([
            'q' => $text,
            'target' => $to,
            'key' => $key,
        ]);
        $resp = @file_get_contents($url);
        if ($resp === false) {
            return self::fallbackTranslate($text);
        }
        $data = json_decode($resp, true);
        return $data['data']['translations'][0]['translatedText'] ?? self::fallbackTranslate($text);
    }
}
