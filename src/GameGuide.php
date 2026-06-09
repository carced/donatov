<?php

declare(strict_types=1);

namespace App;

final class GameGuide
{
    private const ALLOWED_TAGS = [
        'p', 'h1', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'a', 'strong', 'em', 'b', 'i', 'u', 's',
        'br', 'img', 'blockquote', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr', 'span', 'div',
    ];

    /** @return array{title: string, content: string, meta_description: string, resolved_lang: string}|null */
    public static function resolveContent(array $guide, string $visitorLang): ?array
    {
        $titleRu = trim((string) ($guide['title_ru'] ?? ''));
        $titleEn = trim((string) ($guide['title_en'] ?? ''));
        $contentRu = trim((string) ($guide['content_ru'] ?? ''));
        $contentEn = trim((string) ($guide['content_en'] ?? ''));

        $hasRu = $titleRu !== '' && $contentRu !== '';
        $hasEn = $titleEn !== '' && $contentEn !== '';

        if (!$hasRu && !$hasEn) {
            return null;
        }

        if ($hasRu && $hasEn) {
            if ($visitorLang === 'en') {
                return [
                    'title' => $titleEn,
                    'content' => $contentEn,
                    'meta_description' => trim((string) ($guide['meta_description_en'] ?? '')),
                    'resolved_lang' => 'en',
                ];
            }

            return [
                'title' => $titleRu,
                'content' => $contentRu,
                'meta_description' => trim((string) ($guide['meta_description_ru'] ?? '')),
                'resolved_lang' => 'ru',
            ];
        }

        if ($hasEn) {
            return [
                'title' => $titleEn,
                'content' => $contentEn,
                'meta_description' => trim((string) ($guide['meta_description_en'] ?? '')),
                'resolved_lang' => 'en',
            ];
        }

        return [
            'title' => $titleRu,
            'content' => $contentRu,
            'meta_description' => trim((string) ($guide['meta_description_ru'] ?? '')),
            'resolved_lang' => 'ru',
        ];
    }

    public static function sanitizeHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $allowed = '<' . implode('><', self::ALLOWED_TAGS) . '>';
        $html = strip_tags($html, $allowed);

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="utf-8" ?><div id="guide-root">' . $html . '</div>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementById('guide-root');
        if (!$root) {
            return $html;
        }

        self::sanitizeNode($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    private static function sanitizeNode(\DOMNode $node): void
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        /** @var \DOMElement $el */
        $el = $node;
        $tag = strtolower($el->nodeName);

        if (!in_array($tag, self::ALLOWED_TAGS, true)) {
            $el->parentNode?->removeChild($el);

            return;
        }

        $allowedAttrs = match ($tag) {
            'a' => ['href', 'title', 'target', 'rel'],
            'img' => ['src', 'alt', 'title', 'width', 'height'],
            default => ['class'],
        };

        if ($el->hasAttributes()) {
            $remove = [];
            foreach ($el->attributes as $attr) {
                $name = strtolower($attr->name);
                if (!in_array($name, $allowedAttrs, true)) {
                    $remove[] = $name;
                }
            }
            foreach ($remove as $name) {
                $el->removeAttribute($name);
            }
        }

        if ($tag === 'a') {
            $href = trim((string) $el->getAttribute('href'));
            if ($href === '' || (!str_starts_with($href, '/') && !str_starts_with($href, 'http://') && !str_starts_with($href, 'https://') && !str_starts_with($href, 'mailto:'))) {
                $el->removeAttribute('href');
            } elseif (str_starts_with($href, 'http')) {
                $el->setAttribute('rel', 'noopener noreferrer');
                if ($el->getAttribute('target') === '') {
                    $el->setAttribute('target', '_blank');
                }
            }
        }

        if ($tag === 'img') {
            $src = trim((string) $el->getAttribute('src'));
            if ($src === '' || (!str_starts_with($src, '/') && !str_starts_with($src, 'http://') && !str_starts_with($src, 'https://'))) {
                $el->parentNode?->removeChild($el);

                return;
            }
        }

        $children = [];
        foreach ($el->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            self::sanitizeNode($child);
        }
    }

    public static function excerpt(string $html, int $maxLen = 280): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $maxLen - 1)) . '…';
    }

    public static function metaDescription(
        array $resolved,
        string $productName,
        string $siteName,
    ): string {
        $custom = trim($resolved['meta_description'] ?? '');
        if ($custom !== '') {
            return $custom;
        }

        $excerpt = self::excerpt($resolved['content'], 155);
        if ($excerpt !== '') {
            return $excerpt;
        }

        return I18n::t('guide_meta_fallback', [
            'product' => $productName,
            'site' => $siteName,
        ]);
    }

    public static function guideHubUrl(string $goodSlug): string
    {
        return '/guide/' . rawurlencode($goodSlug);
    }

    public static function guideArticleUrl(string $goodSlug, string $articleSlug): string
    {
        return '/guide/' . rawurlencode($goodSlug) . '/' . rawurlencode($articleSlug);
    }

    public static function storeUrl(string $slug): string
    {
        return '/g/' . rawurlencode($slug);
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text !== '' ? $text : 'article';
    }

    /** @return list<array{guide: array, resolved: array, articleUrl: string, excerpt: string}> */
    public static function resolvedArticlesForGood(array $rows, string $lang): array
    {
        $out = [];
        foreach ($rows as $row) {
            $resolved = self::resolveContent($row, $lang);
            if ($resolved === null) {
                continue;
            }
            $out[] = [
                'guide' => $row,
                'resolved' => $resolved,
                'articleUrl' => self::guideArticleUrl(
                    (string) $row['good_slug'],
                    (string) $row['article_slug'],
                ),
                'excerpt' => self::excerpt($resolved['content']),
            ];
        }

        return $out;
    }
}
