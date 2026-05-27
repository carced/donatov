<?php

declare(strict_types=1);

namespace App;

final class GamePopularity
{
    private static ?array $ranks = null;

    public static function rankForSlug(string $slug): int
    {
        $ranks = self::ranks();
        return $ranks[$slug] ?? 9000;
    }

    /** @param list<array> $goods */
    public static function sortGoods(array $goods, string $sort): array
    {
        $sort = $sort ?: 'popular';
        $goods = array_values($goods);

        if ($sort === 'name_asc') {
            usort($goods, fn ($a, $b) => strcasecmp(
                self::sortName($a),
                self::sortName($b),
            ));
            return $goods;
        }
        if ($sort === 'name_desc') {
            usort($goods, fn ($a, $b) => strcasecmp(
                self::sortName($b),
                self::sortName($a),
            ));
            return $goods;
        }

        usort($goods, function ($a, $b) {
            $ra = self::rankForSlug((string) ($a['slug'] ?? ''));
            $rb = self::rankForSlug((string) ($b['slug'] ?? ''));
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }
            return strcasecmp(self::sortName($a), self::sortName($b));
        });

        return $goods;
    }

    private static function sortName(array $good): string
    {
        return I18n::transEntity('good', (string) $good['id'], 'name', $good['name_ru'] ?? '');
    }

    /** @return array<string, int> */
    private static function ranks(): array
    {
        if (self::$ranks === null) {
            $path = dirname(__DIR__) . '/config/game_popularity.php';
            self::$ranks = is_file($path) ? require $path : [];
        }
        return self::$ranks;
    }
}
