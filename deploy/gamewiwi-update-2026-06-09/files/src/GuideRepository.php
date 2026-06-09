<?php

declare(strict_types=1);

namespace App;

use PDO;

final class GuideRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM game_guides WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findArticle(string $goodSlug, string $articleSlug, bool $enabledOnly = true): ?array
    {
        $sql = 'SELECT * FROM game_guides WHERE good_slug = ? AND article_slug = ?';
        if ($enabledOnly) {
            $sql .= ' AND enabled = 1';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$goodSlug, $articleSlug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function listForGood(string $goodSlug, bool $enabledOnly = false): array
    {
        $sql = 'SELECT * FROM game_guides WHERE good_slug = ?';
        if ($enabledOnly) {
            $sql .= ' AND enabled = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, updated_at DESC, id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$goodSlug]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function allEnabled(): array
    {
        return $this->pdo->query(
            'SELECT * FROM game_guides WHERE enabled = 1 ORDER BY good_slug, sort_order, updated_at DESC'
        )->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function allForAdmin(): array
    {
        return $this->pdo->query(
            'SELECT * FROM game_guides ORDER BY good_slug, sort_order, updated_at DESC'
        )->fetchAll() ?: [];
    }

    /** @return list<array{good_slug: string, article_slug: string, updated_at: string}> */
    public function allForSitemap(): array
    {
        return $this->pdo->query(
            'SELECT good_slug, article_slug, updated_at FROM game_guides WHERE enabled = 1 ORDER BY good_slug, article_slug'
        )->fetchAll() ?: [];
    }

    /** @param array<string, mixed> $input */
    public function save(?int $id, string $goodSlug, string $articleSlug, array $input): int
    {
        if ($id !== null && $id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE game_guides SET
                    good_slug = ?, article_slug = ?, title_ru = ?, title_en = ?,
                    content_ru = ?, content_en = ?, meta_description_ru = ?, meta_description_en = ?,
                    enabled = ?, sort_order = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                $goodSlug,
                $articleSlug,
                self::nullIfEmpty($input['title_ru'] ?? null),
                self::nullIfEmpty($input['title_en'] ?? null),
                self::nullIfEmpty($input['content_ru'] ?? null),
                self::nullIfEmpty($input['content_en'] ?? null),
                self::nullIfEmpty($input['meta_description_ru'] ?? null),
                self::nullIfEmpty($input['meta_description_en'] ?? null),
                !empty($input['enabled']) ? 1 : 0,
                (int) ($input['sort_order'] ?? 0),
                $id,
            ]);

            return $id;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO game_guides (
                good_slug, article_slug, title_ru, title_en, content_ru, content_en,
                meta_description_ru, meta_description_en, enabled, sort_order
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $goodSlug,
            $articleSlug,
            self::nullIfEmpty($input['title_ru'] ?? null),
            self::nullIfEmpty($input['title_en'] ?? null),
            self::nullIfEmpty($input['content_ru'] ?? null),
            self::nullIfEmpty($input['content_en'] ?? null),
            self::nullIfEmpty($input['meta_description_ru'] ?? null),
            self::nullIfEmpty($input['meta_description_en'] ?? null),
            !empty($input['enabled']) ? 1 : 0,
            (int) ($input['sort_order'] ?? 0),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM game_guides WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function articleSlugExists(string $goodSlug, string $articleSlug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM game_guides WHERE good_slug = ? AND article_slug = ?';
        $params = [$goodSlug, $articleSlug];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch();
    }

    private static function nullIfEmpty(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }
}
