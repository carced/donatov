<?php

declare(strict_types=1);

namespace App;

use PDO;

final class GuideRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM game_guides WHERE good_slug = ? AND enabled = 1 LIMIT 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findAnyBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM game_guides WHERE good_slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function allEnabled(): array
    {
        return $this->pdo->query(
            'SELECT * FROM game_guides WHERE enabled = 1 ORDER BY updated_at DESC'
        )->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function allForAdmin(): array
    {
        return $this->pdo->query(
            'SELECT * FROM game_guides ORDER BY updated_at DESC'
        )->fetchAll() ?: [];
    }

    /** @return list<array{good_slug: string, updated_at: string}> */
    public function allForSitemap(): array
    {
        return $this->pdo->query(
            'SELECT good_slug, updated_at FROM game_guides WHERE enabled = 1 ORDER BY good_slug'
        )->fetchAll() ?: [];
    }

    /** @param array<string, mixed> $input */
    public function upsert(string $goodSlug, array $input): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO game_guides (
                good_slug, title_ru, title_en, content_ru, content_en,
                meta_description_ru, meta_description_en, enabled
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                title_ru = VALUES(title_ru),
                title_en = VALUES(title_en),
                content_ru = VALUES(content_ru),
                content_en = VALUES(content_en),
                meta_description_ru = VALUES(meta_description_ru),
                meta_description_en = VALUES(meta_description_en),
                enabled = VALUES(enabled)'
        );
        $stmt->execute([
            $goodSlug,
            self::nullIfEmpty($input['title_ru'] ?? null),
            self::nullIfEmpty($input['title_en'] ?? null),
            self::nullIfEmpty($input['content_ru'] ?? null),
            self::nullIfEmpty($input['content_en'] ?? null),
            self::nullIfEmpty($input['meta_description_ru'] ?? null),
            self::nullIfEmpty($input['meta_description_en'] ?? null),
            !empty($input['enabled']) ? 1 : 0,
        ]);
    }

    private static function nullIfEmpty(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }
}
