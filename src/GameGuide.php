<?php

declare(strict_types=1);

namespace App;

final class GameGuide
{
    private const ALLOWED_TAGS = '<p><h2><h3><h4><ul><ol><li><a><strong><em><b><i><br><img><blockquote><table><thead><tbody><tr><th><td><hr><span><div>';

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

        return strip_tags($html, self::ALLOWED_TAGS);
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

    public static function guideUrl(string $slug): string
    {
        return '/guide/' . rawurlencode($slug);
    }

    public static function storeUrl(string $slug): string
    {
        return '/g/' . rawurlencode($slug);
    }
}
