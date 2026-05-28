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


    /** Default balance tiers when a game has no packs in DB. */
    public function ensureDefaultBalancePacks(int $goodId): void
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM packs WHERE good_id = ?');
        $stmt->execute([$goodId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $tiers = [
            [90001, 'Баланс 25$ за 15$', 15.00],
            [90002, 'Баланс 50$ за 25$', 25.00],
            [90003, 'Баланс 100$ за 50$', 50.00],
            [90004, 'Баланс 200$ за 80$', 80.00],
        ];
        $ins = $this->pdo->prepare(
            'INSERT INTO packs (good_id, source_pack_id, name_ru, price_rub_source, price_usd, in_stock)
             VALUES (?, ?, ?, ?, ?, 1)'
        );
        foreach ($tiers as [$sourceId, $nameRu, $usd]) {
            $ins->execute([$goodId, $sourceId, $nameRu, $usd, $usd]);
        }
    }

    public function packsForGood(int $goodId): array
    {
        $this->ensureDefaultBalancePacks($goodId);
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

    public function homeGoods(string $sort = 'popular', int $limit = 0): array
    {
        $rows = $this->pdo->query('SELECT * FROM goods WHERE enabled = 1')->fetchAll() ?: [];
        $sorted = GamePopularity::sortGoods($rows, $sort);
        if ($limit > 0) {
            return array_slice($sorted, 0, $limit);
        }
        return $sorted;
    }


    /** @param list<array<string, mixed>> $goods */
    public function attachMinPrices(array $goods): array
    {
        if ($goods === []) {
            return $goods;
        }
        $ids = array_map(static fn ($g) => (int) $g['id'], $goods);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT good_id, MIN(price_usd) AS min_price_usd FROM packs
             WHERE in_stock = 1 AND good_id IN ($placeholders) GROUP BY good_id"
        );
        $stmt->execute($ids);
        $map = [];
        while ($row = $stmt->fetch()) {
            $map[(int) $row['good_id']] = (float) $row['min_price_usd'];
        }
        foreach ($goods as &$g) {
            $g['min_price_usd'] = $map[(int) $g['id']] ?? 0.0;
        }
        unset($g);
        return $goods;
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


    public function relatedGoods(int $goodId, string $categoryId, int $limit = 6): array
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
        $rows = $stmt->fetchAll() ?: [];

        if (count($rows) >= $limit) {
            return $rows;
        }

        $exclude = [$goodId];
        foreach ($rows as $r) {
            $exclude[] = (int) $r['id'];
        }

        $need = $limit - count($rows);
        $placeholders = implode(',', array_fill(0, count($exclude), '?'));
        $fillSql = "SELECT g.* FROM goods g
             WHERE g.enabled = 1 AND g.id NOT IN ($placeholders)
             ORDER BY g.sort_order, g.name_ru
             LIMIT ?";
        $fillStmt = $this->pdo->prepare($fillSql);
        $i = 1;
        foreach ($exclude as $id) {
            $fillStmt->bindValue($i++, $id, PDO::PARAM_INT);
        }
        $fillStmt->bindValue($i, $need, PDO::PARAM_INT);
        $fillStmt->execute();
        $extra = $fillStmt->fetchAll() ?: [];
        if ($extra !== []) {
            $extra = GamePopularity::sortGoods($extra, 'popular');
            $extra = array_slice($extra, 0, $need);
        }

        return array_merge($rows, $extra);
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
