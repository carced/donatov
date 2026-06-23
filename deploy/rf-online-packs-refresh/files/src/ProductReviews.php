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
        if ($slug === 'rf-online-next') {
            return self::forRfOnlineNext($good);
        }

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

    private const RF_ONLINE_REVIEW_COUNT = 19;

    /** @return list<string> */
    private static function rfOnlineReviewDates(): array
    {
        return [
            '2026-06-17', '2026-06-17', '2026-06-17',
            '2026-06-18', '2026-06-18', '2026-06-18',
            '2026-06-19', '2026-06-19', '2026-06-19',
            '2026-06-20', '2026-06-20', '2026-06-20',
            '2026-06-21', '2026-06-21',
            '2026-06-22', '2026-06-22', '2026-06-22',
            '2026-06-23', '2026-06-23',
        ];
    }

    /** @return list<array{author: string, date: string, rating: int, text: string, lang: string}> */
    private static function forRfOnlineNext(array $good): array
    {
        $name = I18n::transEntity('good', (string) $good['id'], 'name', $good['name_ru']);
        $lang = I18n::lang();
        $dates = self::rfOnlineReviewDates();
        $reviews = [];

        for ($i = 0; $i < self::RF_ONLINE_REVIEW_COUNT; $i++) {
            $seed = crc32('rf-online-next|review|' . $i);
            $reviewLang = (($seed >> 3) & 1) === 0 ? 'ru' : 'en';
            if ($lang === 'ru' && $reviewLang === 'en' && ($seed % 4) !== 0) {
                $reviewLang = 'ru';
            }
            if ($lang === 'en' && $reviewLang === 'ru' && ($seed % 4) !== 0) {
                $reviewLang = 'en';
            }

            $rating = self::rating($seed);
            $text = self::rfOnlineBody($reviewLang, $seed);
            $text = self::humanize($text, $seed);

            $reviews[] = [
                'author' => self::author($reviewLang, $seed),
                'date' => $dates[$i],
                'rating' => $rating,
                'text' => $text,
                'lang' => $reviewLang,
            ];
        }

        usort($reviews, static fn(array $a, array $b): int => strcmp($b['date'], $a['date']) ?: 0);

        return $reviews;
    }

    private static function rfOnlineBody(string $lang, int $seed): string
    {
        if ($lang === 'en') {
            $templates = [
                '10900 diamond pack landed in ~10 min on Hecate2[NA]. Smooth checkout.',
                'Grabbed the weekly summon ticket pack — exactly what the listing said.',
                '3570 diamonds for $15 feels fair vs in-game. USDT payment was quick.',
                'Monthly artifact package showed up same day. No issues with EU server pick.',
                'Third RF Online Next order here — summon packs always match description.',
                'Bought diamonds for my guildmate, nickname + server fields were clear.',
                'Monthly summon tickets credited after one confirmation. Will reorder.',
                'First crypto top-up for RF Online Next — support helped with region select.',
                '3570 pack is my go-to now. Faster than waiting on official bundles.',
                'Weekly ticket pack delivered while I was still in game. Nice.',
                'Big 10900 diamond order for update week — took 15 min, totally fine.',
                'Artifact monthly pack worth it for the price on this site.',
                'NA region + Hecate3 server — everything arrived correctly.',
                'Inanna1[EU] server, diamonds in before maintenance ended.',
                'Summon ticket monthly pack = easy buy, no sketchy middleman.',
            ];
        } else {
            $templates = [
                'Пак 10900 алмазов пришёл минут за 10 на Hecate2[NA]. Оформление простое.',
                'Брал weekly summon ticket pack — всё как в описании.',
                '3570 алмазов за $15 норм по сравнению с игрой. USDT быстро.',
                'Monthly artifact package выдали в тот же день. EU сервер без проблем.',
                'Уже третий заказ RF Online Next — билеты призыва всегда ок.',
                'Брал алмазы другу, поля ник и сервер понятные.',
                'Monthly summon tickets зачислили после одного подтверждения.',
                'Первый раз криптой в RF Online Next — помогли с выбором региона.',
                '3570 пак беру постоянно, быстрее чем ждать официальные наборы.',
                'Weekly ticket pack пришёл пока я ещё в игре был.',
                'Большой заказ 10900 алмазов на патч — мин 15, норм.',
                'Monthly artifact pack выгодный для этой цены.',
                'NA + Hecate3 — всё доставили правильно.',
                'Inanna1[EU], алмазы до конца техработ успели.',
                'Пакет месячных билетов призыва — удобно, без перекупов.',
            ];
        }

        return $templates[$seed % count($templates)];
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
