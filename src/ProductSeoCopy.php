<?php

declare(strict_types=1);

namespace App;

final class ProductSeoCopy
{
    /** Visible SEO paragraph on product page and short card excerpt. */
    public static function description(array $good, float $minPriceUsd = 0): string
    {
        $lang = I18n::lang();
        $name = self::productName($good);
        $currency = self::currencyLine($good);
        $instant = !empty($good['instant']);
        $seed = crc32((string) ($good['slug'] ?? $good['id']));

        if ($lang === 'en') {
            return self::descriptionEn($name, $currency, $minPriceUsd, $instant, $seed);
        }

        return self::descriptionRu($name, $currency, $minPriceUsd, $instant, $seed);
    }

    private static function str_len(string $s): int
    {
        return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
    }

    private static function str_sub(string $s, int $start, int $len): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($s, $start, $len);
        }
        return substr($s, $start, $len);
    }

    public static function excerpt(array $good, float $minPriceUsd = 0, int $maxLen = 140): string
    {
        $text = self::description($good, $minPriceUsd);
        if (self::str_len($text) <= $maxLen) {
            return $text;
        }

        $cut = self::str_sub($text, 0, $maxLen - 1);
        $lastSpace = (function_exists("mb_strrpos") ? mb_strrpos($cut, " ") : strrpos($cut, " "));
        if ($lastSpace !== false && $lastSpace > 60) {
            $cut = self::str_sub($cut, 0, (int)$lastSpace);
        }

        return rtrim($cut, '.,;') . '…';
    }

    public static function metaDescription(array $good, float $minPriceUsd, string $site): string
    {
        $excerpt = self::excerpt($good, $minPriceUsd, 155);
        $lang = I18n::lang();
        if ($lang === 'en') {
            return $excerpt . ' Pay with crypto at ' . $site . '.';
        }

        return $excerpt . ' Оплата криптой на ' . $site . '.';
    }

    private static function productName(array $good): string
    {
        return I18n::transEntity('good', (string) $good['id'], 'name', $good['name_ru']);
    }

    private static function currencyLine(array $good): string
    {
        $ru = trim((string) ($good['currency_name_ru'] ?? ''));
        if ($ru === '') {
            return '';
        }
        $label = I18n::transEntity('good', (string) $good['id'], 'currency', $ru);
        if (I18n::lang() === 'en') {
            return ListingTranslator::toEnglish($label);
        }

        return $label;
    }

    private static function descriptionRu(string $name, string $currency, float $minPrice, bool $instant, int $seed): string
    {
        $variants = [
            '{name} — официальное пополнение{cur} на нашем маркетплейсе. {price} {delivery} Оплата Bitcoin, USDT, Ethereum; зачисление обычно за 1–10 минут после подтверждения сети.',
            'Купить донат для {name}{cur} выгоднее, чем в приложении: {price} {delivery} Без скрытых комиссий, поддержка 24/7, чек на email по желанию.',
            'Пополнение {name}{cur} — быстрый сервис для игроков из СНГ и Европы. {price} {delivery} Крипто-чекаут, прозрачный курс USD, история заказов в личном кабинете.',
            '{name}: внутриигровая валюта{cur} с моментальной выдачей. {price} {delivery} Подходит для UID/логина; можно оплатить реферальным балансом после бесплатной ссылки.',
            'Дешёвый донат {name}{cur} — цены до 40% ниже магазина. {price} {delivery} Автовыдача после оплаты криптой, без регистрации на сторонних площадках.',
            'Сервис пополнения {name}{cur} для тех, кто ценит скорость. {price} {delivery} USDT TRC20, BTC, ETH — выбирайте удобную монету на странице оплаты.',
        ];
        $idx = $seed % count($variants);
        $cur = $currency !== '' ? ' (' . $currency . ')' : '';
        $price = $minPrice > 0
            ? 'от ' . number_format($minPrice, 2, '.', '') . ' USD.'
            : 'Актуальные пакеты в каталоге.';
        $delivery = $instant
            ? 'Моментальная доставка после оплаты.'
            : 'Доставка в течение нескольких минут.';

        return str_replace(
            ['{name}', '{cur}', '{price}', '{delivery}'],
            [$name, $cur, $price, $delivery],
            $variants[$idx],
        );
    }

    private static function descriptionEn(string $name, string $currency, float $minPrice, bool $instant, int $seed): string
    {
        $variants = [
            '{name} top-up{cur} at up to 40% below in-app prices. {price} {delivery} Pay with BTC, USDT, or ETH — balance lands in 1–10 minutes after network confirmation.',
            'Buy {name} currency{cur} with crypto checkout and no hidden fees. {price} {delivery} Works with player ID / login fields; optional email receipt.',
            '{name} donations{cur} for players worldwide. {price} {delivery} Secure wallets, live USD pricing, and referral balance if you share your free link.',
            'Cheap {name} packs{cur} — trusted marketplace delivery. {price} {delivery} Instant automation when marked instant; support if your order needs a manual check.',
            'Get {name}{cur} fast: pick a pack, pay crypto, done. {price} {delivery} Ideal for topping up before events, seasons, or battle passes.',
            '{name} in-game currency{cur} with transparent USD totals. {price} {delivery} TRC20 USDT supported; other coins available on the payment step.',
        ];
        $idx = $seed % count($variants);
        $cur = $currency !== '' ? ' (' . $currency . ')' : '';
        $price = $minPrice > 0
            ? 'From $' . number_format($minPrice, 2) . '.'
            : 'See live packs below.';
        $delivery = $instant
            ? 'Instant delivery after payment.'
            : 'Usually delivered within minutes.';

        return str_replace(
            ['{name}', '{cur}', '{price}', '{delivery}'],
            [$name, $cur, $price, $delivery],
            $variants[$idx],
        );
    }
}
