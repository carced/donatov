<?php

declare(strict_types=1);

namespace App;

use PDO;

final class CatalogRepository
{
    public function __construct(private PDO $pdo) {}

    public function categories(): array
    {
        return $this->pdo->query('SELECT * FROM categories ORDER BY sort_order, id')->fetchAll();
    }

    public function tagsForCategory(string $categoryId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tags WHERE category_id = ? ORDER BY name_ru');
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    public function catalogGoods(?string $categoryId = null, ?string $tagId = null): array
    {
        $sql = 'SELECT g.* FROM goods g WHERE g.enabled = 1';
        $params = [];
        if ($categoryId) {
            $sql .= ' AND g.category_id = ?';
            $params[] = $categoryId;
        }
        if ($tagId && $categoryId) {
            $sql .= ' AND EXISTS (SELECT 1 FROM good_tags gt WHERE gt.good_id = g.id AND gt.tag_id = ? AND gt.category_id = ?)';
            $params[] = $tagId;
            $params[] = $categoryId;
        }
        $sql .= ' ORDER BY g.sort_order, g.name_ru';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function goodBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM goods WHERE slug = ? AND enabled = 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function goodContent(int $goodId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM good_content WHERE good_id = ?');
        $stmt->execute([$goodId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function packsForGood(int $goodId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, pg.source_group_id, pg.name_ru AS group_name
             FROM packs p
             LEFT JOIN pack_groups pg ON pg.id = p.group_id
             WHERE p.good_id = ? AND p.in_stock = 1
             ORDER BY pg.source_group_id, p.price_usd, p.id'
        );
        $stmt->execute([$goodId]);
        return $stmt->fetchAll();
    }

    public function packGroups(int $goodId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pack_groups WHERE good_id = ? ORDER BY source_group_id');
        $stmt->execute([$goodId]);
        return $stmt->fetchAll();
    }

    public function fieldsForGood(int $goodId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM good_fields WHERE good_id = ? ORDER BY sort_order, id');
        $stmt->execute([$goodId]);
        return $stmt->fetchAll();
    }

    public function packById(int $packId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, g.slug AS good_slug, g.name_ru AS good_name
             FROM packs p JOIN goods g ON g.id = p.good_id WHERE p.id = ?'
        );
        $stmt->execute([$packId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function paymentMethods(): array
    {
        return $this->pdo->query('SELECT * FROM payment_methods WHERE enabled = 1 ORDER BY id')->fetchAll();
    }

    public function homeGoods(string $sort = 'popular', int $limit = 48): array
    {
        $rows = $this->pdo->query('SELECT * FROM goods WHERE enabled = 1')->fetchAll() ?: [];
        $sorted = GamePopularity::sortGoods($rows, $sort);
        return array_slice($sorted, 0, $limit);
    }

    public function featuredGoods(int $limit = 12): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM goods WHERE enabled = 1 ORDER BY instant DESC, sort_order LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    public function relatedGoods(int $goodId, string $categoryId, int $limit = 8): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT g.* FROM goods g
             WHERE g.enabled = 1 AND g.category_id = ? AND g.id != ?
             ORDER BY g.sort_order, g.name_ru
             LIMIT ?'
        );
        $stmt->bindValue(1, $categoryId);
        $stmt->bindValue(2, $goodId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array{slug: string, updated_at: string}> */
    public function allGoodsForSitemap(): array
    {
        return $this->pdo->query(
            'SELECT slug, updated_at FROM goods WHERE enabled = 1 ORDER BY id'
        )->fetchAll() ?: [];
    }
    public function latestFx(): ?array
    {
        $row = $this->pdo->query('SELECT * FROM fx_rates ORDER BY rate_date DESC LIMIT 1')->fetch();
        return $row ?: null;
    }
}
