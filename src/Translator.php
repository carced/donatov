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
            'deepl' => self::deepl($text, $from, $to),
            'google' => self::google($text, $from, $to),
            'libretranslate' => self::libretranslate($text, $from, $to),
            default => self::fallbackTranslate($text),
        };
        self::$cache[$key] = $result;
        // Small throttle to reduce request bursts.
usleep(20000);
        return $result;
    }

    private static function translateA(string $text, string $from, string $to): string
    {
        $sl = rawurlencode($from);
        $tl = rawurlencode($to);
        $q = rawurlencode($text);
        $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl={$sl}&tl={$tl}&dt=t&q={$q}";
        $resp = @file_get_contents($url);
        if ($resp === false) {
            return self::fallbackTranslate($text);
        }
        $data = json_decode($resp, true);
        if (!is_array($data) || empty($data[0]) || !is_array($data[0])) {
            return self::fallbackTranslate($text);
        }

        $out = '';
        foreach ($data[0] as $piece) {
            if (is_array($piece) && array_key_exists(0, $piece) && $piece[0] !== null) {
                $out .= (string) $piece[0];
            }
        }

        return $out !== '' ? $out : self::fallbackTranslate($text);
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
        if (!$key) {
            // If no API key is configured, fall back to a public endpoint.
            return self::translateA($text, $from, $to);
        }
        $headers[] = 'Authorization: Bearer ' . $key;
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

    private static function deepl(string $text, string $from, string $to): string
    {
        $key = Config::get('TRANSLATION_API_KEY');
        if (!$key) {
            return self::translateA($text, $from, $to);
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

    private static function google(string $text, string $from, string $to): string
    {
        $key = Config::get('TRANSLATION_API_KEY');
        if (!$key) {
            return self::translateA($text, $from, $to);
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
