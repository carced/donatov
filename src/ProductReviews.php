<?php

declare(strict_types=1);

namespace App;

final class ProductReviews
{
    private const COUNT = 36;

    /** @return list<array{author: string, date: string, rating: int, text: string, lang: string}> */
    public static function forGood(array $good): array
    {
        $slug = (string) ($good['slug'] ?? $good['id']);
        $name = I18n::transEntity('good', (string) $good['id'], 'name', $good['name_ru']);
        $lang = I18n::lang();
        $reviews = [];

        for ($i = 0; $i < self::COUNT; $i++) {
            $seed = crc32($slug . '|' . $i);
            $reviewLang = (($seed >> 3) & 1) === 0 ? 'ru' : 'en';
            if ($lang === 'ru' && $reviewLang === 'en' && ($seed % 5) !== 0) {
                $reviewLang = 'ru';
            }
            if ($lang === 'en' && $reviewLang === 'ru' && ($seed % 5) !== 0) {
                $reviewLang = 'en';
            }

            $rating = self::rating($seed);
            $text = self::body($name, $reviewLang, $seed);
            $text = self::humanize($text, $seed);

            $reviews[] = [
                'author' => self::author($reviewLang, $seed),
                'date' => self::date($seed),
                'rating' => $rating,
                'text' => $text,
                'lang' => $reviewLang,
            ];
        }

        return $reviews;
    }

    /** @param list<array{rating: int}> $reviews */
    public static function aggregate(array $reviews): array
    {
        if ($reviews === []) {
            return ['average' => 4.8, 'count' => 0];
        }
        $sum = 0;
        foreach ($reviews as $r) {
            $sum += (int) $r['rating'];
        }

        return [
            'average' => round($sum / count($reviews), 1),
            'count' => count($reviews),
        ];
    }

    private static function rating(int $seed): int
    {
        $r = $seed % 100;
        if ($r < 72) {
            return 5;
        }
        if ($r < 94) {
            return 4;
        }

        return 5;
    }

    private static function author(string $lang, int $seed): string
    {
        $ru = [
            'Артём К.', 'Маша_92', 'dima_topup', 'Сергей', 'Катя из Питера', 'Илья', 'Никита777',
            'olya_donat', 'Влад', 'Анонимный игрок', 'Руслан', 'Соня', 'Макс', 'Глеб', 'Таня',
            'kirill_gg', 'Егор', 'Лена', 'Павел', 'Юля', 'Стас', 'Вика', 'Рома', 'Настя',
        ];
        $en = [
            'Mike T.', 'Sarah_K', 'jayplays', 'Chris', 'emma.w', 'Alex99', 'lootfan',
            'Tommy', 'nina_r', 'Guest_buyer', 'Daniel', 'Lily', 'Ryan', 'Omar', 'Kate',
            'pixelhero', 'Brandon', 'Mia', 'steve_g', 'Chloe', 'Leo', 'Anna', 'Nick', 'Zoe',
        ];
        $pool = $lang === 'en' ? $en : $ru;

        return $pool[$seed % count($pool)];
    }

    private static function date(int $seed): string
    {
        $daysAgo = ($seed % 170) + 1;
        $ts = strtotime('-' . $daysAgo . ' days');

        return date('Y-m-d', $ts ?: time());
    }

    private static function body(string $product, string $lang, int $seed): string
    {
        if ($lang === 'en') {
            $templates = [
                'Ordered {p} late at night, USDT went through in like 8 min. All good.',
                '{p} creditted faster than i expected tbh. Will buy again',
                'Been using this shop for {p} for 3 months — no drama, prices ok',
                'First time crypto pay for {p} — support answered quick when i pasted wrong memo',
                'Got my {p} pack, everything matched the listing. 5/5',
                '{p} top up worked after one confirmation. Recommended if you hate app store fees',
                'Not gonna lie was scared to pay crypto but {p} arrived fine',
                'Bought small pack on {p} to test — instant enough for me',
                '{p} delivery took ~12 min on sunday still happy',
                'My kid wanted {p} , did it from phone, easy checkout',
                'Solid service for {p}. Only wish there was apple pay but crypto is fine',
                'Third order for {p} — same speed every time',
                'Price for {p} was lower than local reseller',
                'Everything for {p} as described, no ban or smth weird',
                'Quick {p} refill, used btc, fee was annoying but expected',
            ];
        } else {
            $templates = [
                'Брал {p} ночью, USDT пришло минут за 8 — всё ок',
                'Пополнил {p} быстрее чем думал, цена норм буду еще',
                'Уже третий раз {p} беру тут, без косяков пока',
                'Первый раз платил криптой за {p} — чуть перепутал коммент но выдали',
                'На {p} всё пришло как в описании, рекомендую',
                'Для {p} удобно что не надо регаться на куче сайтов',
                'Ждал {p} минут 15, норм для выходного',
                'Дочке на {p} покупал, оплатил с телефона без проблем',
                'Цена на {p} ниже чем у перекупов в чате',
                'Заказал {p} — пришло, аккаунт целый, спасибо',
                'Норм сервис для {p}, хотелось бы СБП но крипта тоже ок',
                'Второй раз {p}, так же быстро как в первый',
                'Пополнение {p} после обновы игры, всё зачислилось',
                'Брал мелкий пак {p} на пробу — сработало',
                'На {p} чуть задержка была мин 20 но выдали, ставлю 4',
            ];
        }

        $text = str_replace('{p}', $product, $templates[$seed % count($templates)]);

        return $text;
    }

    private static function humanize(string $text, int $seed): string
    {
        $mode = $seed % 7;
        return match ($mode) {
            0 => self::dropComma($text),
            1 => self::doubleSpace($text),
            2 => self::missingDot($text),
            3 => self::swapLetters($text),
            4 => self::lowerStart($text),
            5 => self::extraEllipsis($text),
            default => $text,
        };
    }

    private static function dropComma(string $s): string
    {
        return str_replace(',', '', $s);
    }

    private static function doubleSpace(string $s): string
    {
        $pos = (int) (strlen($s) / 2);
        if ($pos > 0 && $pos < strlen($s)) {
            return substr($s, 0, $pos) . '  ' . substr($s, $pos);
        }

        return $s;
    }

    private static function missingDot(string $s): string
    {
        return rtrim($s, '.');
    }

    private static function swapLetters(string $s): string
    {
        if (strlen($s) < 6) {
            return $s;
        }
        $i = 3;
        return substr($s, 0, $i) . $s[$i + 1] . $s[$i] . substr($s, $i + 2);
    }

    private static function lowerStart(string $s): string
    {
        return lcfirst($s);
    }

    private static function extraEllipsis(string $s): string
    {
        return rtrim($s, '.') . '..';
    }
}
