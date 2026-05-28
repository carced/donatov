<?php

declare(strict_types=1);

namespace App;

final class ProductListingSeo
{
    private static function h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param list<array<string, mixed>> $packs
     * @param list<array<string, mixed>> $relatedGoods
     * @return array{intro: string, packs_heading: string, packs_html: string, related_heading: string, related_html: string}
     */
    public static function blocks(array $good, array $packs, array $relatedGoods, float $minPriceUsd): array
    {
        require_once dirname(__DIR__) . '/templates/helpers.php';
        $lang = I18n::lang();
        $name = GoodName::display($good);
        $currency = self::currencyLine($good);
        $packsInStock = array_values(array_filter($packs, static fn ($p) => !empty($p['in_stock'])));
        $packCount = count($packsInStock);
        $seed = crc32((string) ($good['slug'] ?? $good['id']));

        $intro = $lang === 'en'
            ? self::introEn($name, $currency, $packCount, $minPriceUsd, $relatedGoods, $seed)
            : self::introRu($name, $currency, $packCount, $minPriceUsd, $relatedGoods, $seed);

        return [
            'intro' => $intro,
            'packs_heading' => I18n::t('seo_listing_packs_heading', ['product' => $name]),
            'packs_html' => self::packListHtml($packsInStock),
            'related_heading' => I18n::t('seo_listing_related_heading'),
            'related_html' => self::relatedLinksHtml($relatedGoods, $name),
        ];
    }

    /**
     * @param list<array<string, mixed>> $packs
     */
    private static function packListHtml(array $packs): string
    {
        if ($packs === []) {
            return '<p class="product-listing-seo__empty">' . htmlspecialchars(I18n::t('out_of_stock'), ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $items = [];
        $max = min(16, count($packs));
        for ($i = 0; $i < $max; $i++) {
            $pack = $packs[$i];
            $label = \pack_name($pack);
            $price = Currency::formatUsd((float) $pack['price_usd']);
            $id = (int) $pack['id'];
            $items[] = '<li><a href="#pack-' . $id . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                . '</a> <span class="product-listing-seo__price">(' . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . ')</span></li>';
        }

        if (count($packs) > $max) {
            $items[] = '<li><a href="#buy-section">' . htmlspecialchars(I18n::t('seo_listing_more_packs'), ENT_QUOTES, 'UTF-8') . '</a></li>';
        }

        return '<ul class="product-listing-seo__pack-list">' . implode('', $items) . '</ul>';
    }

    /**
     * @param list<array<string, mixed>> $relatedGoods
     */
    private static function relatedLinksHtml(array $relatedGoods, string $currentName): string
    {
        if ($relatedGoods === []) {
            return '<p><a href="/catalog">' . htmlspecialchars(I18n::t('seo_browse_catalog'), ENT_QUOTES, 'UTF-8') . '</a></p>';
        }

        $links = [];
        foreach ($relatedGoods as $g) {
            $title = GoodName::display($g);
            $slug = rawurlencode((string) $g['slug']);
            $links[] = '<a href="/g/' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '" title="'
                . htmlspecialchars(I18n::t('seo_buy_title', ['product' => $title]), ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a>';
        }

        $lang = I18n::lang();
        $lead = $lang === 'en'
            ? 'Players who top up <strong>' . htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8') . '</strong> often also use:'
            : 'Игроки, которые пополняют <strong>' . htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8') . '</strong>, часто берут также:';

        return '<p class="product-listing-seo__related-lead">' . $lead . '</p>'
            . '<p class="product-listing-seo__related-links">' . implode(' · ', $links) . '</p>';
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

    /**
     * @param list<array<string, mixed>> $relatedGoods
     */
    private static function introEn(
        string $name,
        string $currency,
        int $packCount,
        float $minPriceUsd,
        array $relatedGoods,
        int $seed,
    ): string {
        $name = self::h($name);
        $currency = self::h($currency);
        $cur = $currency !== '' ? ' (' . $currency . ')' : '';
        $price = $minPriceUsd > 0 ? ' from ' . Currency::formatUsd($minPriceUsd) : '';
        $relatedPhrase = self::inlineRelatedEn($relatedGoods, $seed);
        $variants = [
            "This page lists every <strong>{$name}</strong> top-up{$cur} you can buy with crypto{$price}. "
            . "Below are <strong>{$packCount}</strong> in-stock lots — pick one, pay with BTC/USDT/ETH, and get fast delivery. "
            . "{$relatedPhrase}",
            "Buy <strong>{$name}</strong> currency{$cur} at GameWiwi.com{$price}. "
            . "We show <strong>{$packCount}</strong> live packs with transparent USD prices and a simple checkout. "
            . "{$relatedPhrase}",
            "<strong>{$name}</strong> donations and packs{$cur} in one place{$price}. "
            . "Compare <strong>{$packCount}</strong> offers on this listing, then pay in crypto — no hidden fees. "
            . "{$relatedPhrase}",
        ];

        return $variants[$seed % count($variants)];
    }

    /**
     * @param list<array<string, mixed>> $relatedGoods
     */
    private static function introRu(
        string $name,
        string $currency,
        int $packCount,
        float $minPriceUsd,
        array $relatedGoods,
        int $seed,
    ): string {
        $name = self::h($name);
        $currency = self::h($currency);
        $cur = $currency !== '' ? ' (' . $currency . ')' : '';
        $price = $minPriceUsd > 0 ? ' от ' . number_format($minPriceUsd, 2, '.', '') . ' USD' : '';
        $relatedPhrase = self::inlineRelatedRu($relatedGoods, $seed);
        $variants = [
            "На этой странице собраны все лоты для <strong>{$name}</strong>{$cur} с оплатой криптой{$price}. "
            . "Ниже — <strong>{$packCount}</strong> актуальных предложений: выберите пакет, оплатите BTC/USDT/ETH и получите быструю выдачу. "
            . "{$relatedPhrase}",
            "Пополнение <strong>{$name}</strong>{$cur} на GameWiwi.com{$price}. "
            . "В каталоге <strong>{$packCount}</strong> лотов с ценой в USD и простым оформлением. "
            . "{$relatedPhrase}",
            "Донат и пакеты для <strong>{$name}</strong>{$cur} в одном месте{$price}. "
            . "Сравните <strong>{$packCount}</strong> вариантов в списке ниже и оплатите криптовалютой. "
            . "{$relatedPhrase}",
        ];

        return $variants[$seed % count($variants)];
    }

    /**
     * @param list<array<string, mixed>> $relatedGoods
     */
    private static function inlineRelatedEn(array $relatedGoods, int $seed): string
    {
        if ($relatedGoods === []) {
            return 'Browse the <a href="/catalog">full catalog</a> for more games.';
        }
        $pick = array_slice($relatedGoods, 0, min(3, count($relatedGoods)));
        $links = [];
        foreach ($pick as $g) {
            $t = GoodName::display($g);
            $links[] = '<a href="/g/' . htmlspecialchars(rawurlencode((string) $g['slug']), ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '</a>';
        }
        $joined = implode(', ', array_slice($links, 0, -1)) . (count($links) > 1 ? ' and ' . $links[count($links) - 1] : $links[0]);

        return 'You may also like: ' . $joined . '.';
    }

    /**
     * @param list<array<string, mixed>> $relatedGoods
     */
    private static function inlineRelatedRu(array $relatedGoods, int $seed): string
    {
        if ($relatedGoods === []) {
            return 'Смотрите <a href="/catalog">весь каталог</a> игр.';
        }
        $pick = array_slice($relatedGoods, 0, min(3, count($relatedGoods)));
        $links = [];
        foreach ($pick as $g) {
            $t = GoodName::display($g);
            $links[] = '<a href="/g/' . htmlspecialchars(rawurlencode((string) $g['slug']), ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '</a>';
        }
        $n = count($links);
        if ($n === 1) {
            $joined = $links[0];
        } elseif ($n === 2) {
            $joined = $links[0] . ' и ' . $links[1];
        } else {
            $joined = $links[0] . ', ' . $links[1] . ' и ' . $links[2];
        }

        return 'Также смотрят: ' . $joined . '.';
    }
}
