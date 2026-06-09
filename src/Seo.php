<?php

declare(strict_types=1);

namespace App;

final class Seo
{
    /** @return array{title: string, description: string, canonical: string, robots: string, og_type: string, breadcrumbs: list<array{label: string, url: ?string}>, json_ld: list<array<string, mixed>>} */
    public static function forTemplate(string $template, array $data, CatalogRepository $catalog): array
    {
        $lang = I18n::lang();
        $site = Config::get('APP_NAME', 'GameWiwi.com');
        $base = self::siteUrl();

        $ctx = [
            'title' => $site,
            'description' => I18n::t('seo_default_description', ['site' => $site]),
            'canonical' => self::absoluteUrl(self::currentPath()),
            'robots' => 'index, follow, max-image-preview:large',
            'og_type' => 'website',
            'breadcrumbs' => [],
            'json_ld' => [self::organizationSchema($site, $base), self::websiteSchema($site, $base, $lang)],
        ];

        return match ($template) {
            'home' => self::home($ctx, $site, $base, $lang),
            'catalog' => self::catalog($ctx, $data, $catalog, $site, $base, $lang),
            'good' => self::good($ctx, $data, $catalog, $site, $base, $lang),
            'checkout' => self::checkout($ctx, $site, $base, $lang),
            'order' => self::order($ctx, $data, $site, $base, $lang),
            'referral' => self::referral($ctx, $site, $base, $lang),
            'guide' => self::guideArticle($ctx, $data, $catalog, $site, $base, $lang),
            'guide_hub' => self::guideHub($ctx, $data, $site, $base, $lang),
            'guides_index' => self::guidesIndex($ctx, $site, $base, $lang),
            '404' => self::notFound($ctx, $site, $base),
            default => $ctx,
        };
    }

    public static function siteUrl(): string
    {
        return rtrim(Config::get('APP_URL', 'http://localhost:8080'), '/');
    }

    public static function absoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $path = '/' . ltrim($path, '/');
        return self::siteUrl() . $path;
    }

    public static function currentPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = parse_url($uri, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            unset($params['lang']);
            if ($params !== []) {
                return $path . '?' . http_build_query($params);
            }
        }
        return $path === '' ? '/' : $path;
    }

    /** @param list<array{slug: string, updated_at?: string}> $goods */
    /** @param list<array{good_slug: string, article_slug: string, updated_at?: string}> $guides */
    public static function sitemapXml(array $goods, array $guides, string $lang): string
    {
        $base = self::siteUrl();
        $now = date('c');
        $urls = [
            ['loc' => $base . '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => $base . '/catalog', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => $base . '/guides', 'priority' => '0.85', 'changefreq' => 'weekly'],
            ['loc' => $base . '/referral', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ];
        foreach ($goods as $g) {
            $urls[] = [
                'loc' => $base . '/g/' . rawurlencode($g['slug']),
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => isset($g['updated_at']) ? date('c', strtotime($g['updated_at'])) : $now,
            ];
        }
        $hubSlugs = [];
        foreach ($guides as $guide) {
            $goodSlug = (string) $guide['good_slug'];
            $articleSlug = (string) $guide['article_slug'];
            $hubSlugs[$goodSlug] = true;
            $urls[] = [
                'loc' => $base . '/guide/' . rawurlencode($goodSlug) . '/' . rawurlencode($articleSlug),
                'priority' => '0.75',
                'changefreq' => 'weekly',
                'lastmod' => isset($guide['updated_at']) ? date('c', strtotime($guide['updated_at'])) : $now,
            ];
        }
        foreach (array_keys($hubSlugs) as $hubSlug) {
            $urls[] = [
                'loc' => $base . '/guide/' . rawurlencode($hubSlug),
                'priority' => '0.72',
                'changefreq' => 'weekly',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $xml .= ' xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            if (!empty($u['lastmod'])) {
                $xml .= '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
            }
            $xml .= '    <changefreq>' . ($u['changefreq'] ?? 'weekly') . "</changefreq>\n";
            $xml .= '    <priority>' . ($u['priority'] ?? '0.5') . "</priority>\n";
            $other = $lang === 'ru' ? 'en' : 'ru';
            $sep = str_contains($u['loc'], '?') ? '&' : '?';
            $xml .= '    <xhtml:link rel="alternate" hreflang="' . $other . '" href="'
                . htmlspecialchars($u['loc'] . $sep . 'lang=' . $other, ENT_XML1) . "\" />\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';
        return $xml;
    }

    /** @param array<string, mixed> $ctx */
    private static function home(array $ctx, string $site, string $base, string $lang): array
    {
        $ctx['title'] = I18n::t('seo_home_title', ['site' => $site]);
        $ctx['description'] = I18n::t('seo_home_description', ['site' => $site]);
        $ctx['canonical'] = self::absoluteUrl('/');
        $ctx['breadcrumbs'] = [['label' => I18n::t('nav_home'), 'url' => null]];
        $ctx['json_ld'][] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $ctx['title'],
            'description' => $ctx['description'],
            'url' => $ctx['canonical'],
            'inLanguage' => $lang,
        ];
        return $ctx;
    }

    /** @param array<string, mixed> $ctx */
    private static function catalog(array $ctx, array $data, CatalogRepository $catalog, string $site, string $base, string $lang): array
    {
        $categoryId = $data['categoryId'] ?? null;
        $catName = I18n::t('catalog_title');
        if ($categoryId) {
            foreach ($catalog->categories() as $cat) {
                if ($cat['id'] === $categoryId) {
                    $catName = I18n::transEntity('category', $cat['id'], 'name', $cat['name_ru']);
                    break;
                }
            }
        }
        $ctx['title'] = I18n::t('seo_catalog_title', ['category' => $catName, 'site' => $site]);
        $ctx['description'] = I18n::t('seo_catalog_description', ['category' => $catName, 'site' => $site]);
        $path = '/catalog' . ($categoryId ? '?category=' . rawurlencode((string) $categoryId) : '');
        $ctx['canonical'] = self::absoluteUrl($path);
        $ctx['breadcrumbs'] = [
            ['label' => I18n::t('nav_home'), 'url' => '/'],
            ['label' => I18n::t('nav_catalog'), 'url' => null],
        ];
        if ($categoryId) {
            $ctx['breadcrumbs'] = [
                ['label' => I18n::t('nav_home'), 'url' => '/'],
                ['label' => I18n::t('nav_catalog'), 'url' => '/catalog'],
                ['label' => $catName, 'url' => null],
            ];
        }
        return $ctx;
    }

    /** @param array<string, mixed> $ctx */
    private static function good(array $ctx, array $data, CatalogRepository $catalog, string $site, string $base, string $lang): array
    {
        $good = $data['good'];
        $name = self::goodDisplayName($good);
        $minPrice = $data['minPriceUsd'] ?? 0;
        $slug = $good['slug'];
        $ctx['title'] = I18n::t('seo_product_title', ['product' => $name, 'site' => $site]);
        $ctx['description'] = ProductSeoCopy::metaDescription($good, (float) $minPrice, $site);
        $ctx['canonical'] = self::absoluteUrl('/g/' . $slug);
        $ctx['og_type'] = 'product';

        $crumbs = [
            ['label' => I18n::t('nav_home'), 'url' => '/'],
            ['label' => I18n::t('nav_catalog'), 'url' => '/catalog'],
        ];
        $categoryId = $good['category_id'] ?? null;
        if ($categoryId) {
            foreach ($catalog->categories() as $cat) {
                if ($cat['id'] === $categoryId) {
                    $catLabel = I18n::transEntity('category', $cat['id'], 'name', $cat['name_ru']);
                    $crumbs[] = [
                        'label' => $catLabel,
                        'url' => '/catalog?category=' . rawurlencode((string) $categoryId),
                    ];
                    break;
                }
            }
        }
        $crumbs[] = ['label' => $name, 'url' => null];
        $ctx['breadcrumbs'] = $crumbs;

        $cover = $good['cover_path'] ?? $good['cover_url'] ?? '/assets/placeholder.png';
        $image = self::absoluteUrl($cover);
        $ctx['json_ld'][] = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $name,
            'description' => $ctx['description'],
            'image' => $image,
            'url' => $ctx['canonical'],
            'sku' => (string) $good['id'],
            'brand' => ['@type' => 'Brand', 'name' => $site],
            'aggregateRating' => self::productAggregateRating($good),
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'USD',
                'lowPrice' => number_format((float) $minPrice, 2, '.', ''),
                'availability' => 'https://schema.org/InStock',
                'url' => $ctx['canonical'],
            ],
        ];
        $ctx['json_ld'][] = self::breadcrumbListSchema($crumbs, $base);
        $ctx['json_ld'][] = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => I18n::t('seo_faq_crypto_q', ['product' => $name]),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => I18n::t('seo_faq_crypto_a', ['product' => $name]),
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => I18n::t('seo_faq_delivery_q', ['product' => $name]),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => I18n::t('seo_faq_delivery_a'),
                    ],
                ],
            ],
        ];
        return $ctx;
    }

    /** @param array<string, mixed> $ctx */
    private static function checkout(array $ctx, string $site, string $base, string $lang): array
    {
        $ctx['title'] = I18n::t('seo_checkout_title', ['site' => $site]);
        $ctx['description'] = I18n::t('seo_checkout_description');
        $ctx['canonical'] = self::absoluteUrl('/checkout');
        $ctx['robots'] = 'noindex, follow';
        $ctx['breadcrumbs'] = [
            ['label' => I18n::t('nav_home'), 'url' => '/'],
            ['label' => I18n::t('checkout'), 'url' => null],
        ];
        return $ctx;
    }

    /** @param array<string, mixed> $ctx */
    private static function order(array $ctx, array $data, string $site, string $base, string $lang): array
    {
        $ctx['title'] = I18n::t('seo_order_title', ['site' => $site]);
        $ctx['description'] = I18n::t('seo_order_description');
        $ctx['robots'] = 'noindex, nofollow';
        $ctx['breadcrumbs'] = [
            ['label' => I18n::t('nav_home'), 'url' => '/'],
            ['label' => I18n::t('order_success'), 'url' => null],
        ];
        return $ctx;
    }

    /** @param array<string, mixed> $ctx */
    private static function referral(array $ctx, string $site, string $base, string $lang): array
    {
        $ctx['title'] = I18n::t('seo_referral_title', ['site' => $site]);
        $ctx['description'] = I18n::t('seo_referral_description');
        $ctx['canonical'] = self::absoluteUrl('/referral');
        $ctx['breadcrumbs'] = [
            ['label' => I18n::t('nav_home'), 'url' => '/'],
            ['label' => I18n::t('nav_referral'), 'url' => null],
        ];
        return $ctx;
    }

    /** @param array<string, mixed> $ctx */
    private static function notFound(array $ctx, string $site, string $base): array
    {
        $ctx['title'] = I18n::t('seo_404_title', ['site' => $site]);
        $ctx['description'] = I18n::t('seo_404_description');
        $ctx['robots'] = 'noindex, nofollow';
        return $ctx;
    }

    private static function organizationSchema(string $site, string $base): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $site,
            'url' => $base,
        ];
    }

    private static function websiteSchema(string $site, string $base, string $lang): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $site,
            'url' => $base,
            'inLanguage' => $lang,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $base . '/catalog?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /** @param list<array{label: string, url: ?string}> $crumbs */
    private static function breadcrumbListSchema(array $crumbs, string $base): array
    {
        $items = [];
        $pos = 1;
        foreach ($crumbs as $crumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'name' => $crumb['label'],
            ];
            if ($crumb['url'] !== null) {
                $item['item'] = self::absoluteUrl($crumb['url']);
            }
            $items[] = $item;
        }
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private static function goodDisplayName(array $good): string
    {
        return GoodName::display($good);
    }


    /** @return array<string, mixed> */
    private static function productAggregateRating(array $good): array
    {
        $reviews = ProductReviews::forGood($good);
        $stats = ProductReviews::aggregate($reviews);
        return [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $stats['average'],
            'reviewCount' => (string) $stats['count'],
            'bestRating' => '5',
            'worstRating' => '4',
        ];
    }

    /** @param array<string, mixed> $ctx */
    private static function guideArticle(
        array $ctx,
        array $data,
        CatalogRepository $catalog,
        string $site,
        string $base,
        string $lang,
    ): array {
        $resolved = $data['resolved'];
        $good = $data['good'];
        $productName = $data['productName'];
        $goodSlug = (string) $good['slug'];
        $articleSlug = (string) ($data['articleSlug'] ?? $data['guideRow']['article_slug'] ?? '');
        $ctx['title'] = I18n::t('seo_guide_article_title', [
            'article' => $resolved['title'],
            'product' => $productName,
            'site' => $site,
        ]);
        $ctx['description'] = GameGuide::metaDescription($resolved, $productName, $site);
        $ctx['canonical'] = self::absoluteUrl('/guide/' . $goodSlug . '/' . $articleSlug);
        $ctx['og_type'] = 'article';
        $crumbs = [
            ['label' => I18n::t('nav_home'), 'url' => '/'],
            ['label' => I18n::t('nav_guides'), 'url' => '/guides'],
            ['label' => $productName, 'url' => '/guide/' . $goodSlug],
            ['label' => $resolved['title'], 'url' => null],
        ];
        $ctx['breadcrumbs'] = $crumbs;
        $cover = $good['cover_path'] ?? $good['cover_url'] ?? '/assets/placeholder.png';
        $ctx['json_ld'][] = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $resolved['title'],
            'description' => $ctx['description'],
            'image' => self::absoluteUrl($cover),
            'url' => $ctx['canonical'],
            'inLanguage' => $resolved['resolved_lang'],
            'author' => ['@type' => 'Organization', 'name' => $site],
            'publisher' => ['@type' => 'Organization', 'name' => $site, 'url' => $base],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $ctx['canonical']],
            'about' => [
                '@type' => 'Product',
                'name' => $productName,
                'url' => self::absoluteUrl('/g/' . $goodSlug),
            ],
        ];
        $ctx['json_ld'][] = self::breadcrumbListSchema($crumbs, $base);
        return $ctx;
    }

    /** @param array<string, mixed> $ctx */
    private static function guideHub(
        array $ctx,
        array $data,
        string $site,
        string $base,
        string $lang,
    ): array {
        $productName = $data['productName'];
        $goodSlug = (string) $data['goodSlug'];
        $ctx['title'] = I18n::t('seo_guide_hub_title', ['product' => $productName, 'site' => $site]);
        $ctx['description'] = I18n::t('seo_guide_hub_description', ['product' => $productName, 'site' => $site]);
        $ctx['canonical'] = self::absoluteUrl('/guide/' . $goodSlug);
        $ctx['breadcrumbs'] = [
            ['label' => I18n::t('nav_home'), 'url' => '/'],
            ['label' => I18n::t('nav_guides'), 'url' => '/guides'],
            ['label' => $productName, 'url' => null],
        ];
        $ctx['json_ld'][] = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $ctx['title'],
            'description' => $ctx['description'],
            'url' => $ctx['canonical'],
            'inLanguage' => $lang,
        ];
        return $ctx;
    }

    /** @param array<string, mixed> $ctx */
    private static function guidesIndex(array $ctx, string $site, string $base, string $lang): array
    {
        $ctx['title'] = I18n::t('seo_guides_index_title', ['site' => $site]);
        $ctx['description'] = I18n::t('seo_guides_index_description', ['site' => $site]);
        $ctx['canonical'] = self::absoluteUrl('/guides');
        $ctx['breadcrumbs'] = [
            ['label' => I18n::t('nav_home'), 'url' => '/'],
            ['label' => I18n::t('nav_guides'), 'url' => null],
        ];
        $ctx['json_ld'][] = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $ctx['title'],
            'description' => $ctx['description'],
            'url' => $ctx['canonical'],
            'inLanguage' => $lang,
        ];
        return $ctx;
    }

    /** @return list<array{label: string, href: string, title?: string}> */
    public static function footerLinks(CatalogRepository $catalog): array
    {
        $links = [
            ['label' => I18n::t('nav_home'), 'href' => '/', 'title' => I18n::t('seo_link_home_title')],
            ['label' => I18n::t('nav_catalog'), 'href' => '/catalog', 'title' => I18n::t('seo_link_catalog_title')],
            ['label' => I18n::t('nav_guides'), 'href' => '/guides', 'title' => I18n::t('seo_link_guides_title')],
            ['label' => I18n::t('nav_referral'), 'href' => '/referral', 'title' => I18n::t('seo_link_referral_title')],
        ];
        foreach (array_slice($catalog->categories(), 0, 6) as $cat) {
            $links[] = [
                'label' => I18n::transEntity('category', $cat['id'], 'name', $cat['name_ru']),
                'href' => '/catalog?category=' . rawurlencode((string) $cat['id']),
                'title' => I18n::t('seo_link_category_title', [
                    'category' => I18n::transEntity('category', $cat['id'], 'name', $cat['name_ru']),
                ]),
            ];
        }
        return $links;
    }
}
