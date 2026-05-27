<?php

declare(strict_types=1);

namespace App;

use PDO;

final class OrderService
{
    public function __construct(
        private PDO $pdo,
        private CatalogRepository $catalog,
    ) {}

    public function getCart(): array
    {
        return $_SESSION['cart'] ?? ['items' => [], 'fields' => []];
    }

    public function setCart(array $cart): void
    {
        $_SESSION['cart'] = $cart;
    }

    public function addPack(int $packId, int $qty = 1): void
    {
        $cart = $this->getCart();
        $found = false;
        foreach ($cart['items'] as &$item) {
            if ((int) $item['pack_id'] === $packId) {
                $item['qty'] = (int) $item['qty'] + $qty;
                $found = true;
                break;
            }
        }
        unset($item);
        if (!$found) {
            $cart['items'][] = ['pack_id' => $packId, 'qty' => $qty];
        }
        $this->setCart($cart);
    }

    public function removePack(int $packId): void
    {
        $cart = $this->getCart();
        $cart['items'] = array_values(array_filter(
            $cart['items'],
            fn ($i) => (int) $i['pack_id'] !== $packId
        ));
        $this->setCart($cart);
    }

    public function clearCart(): void
    {
        $this->setCart(['items' => [], 'fields' => []]);
    }

    public function setGoodFields(int $goodId, array $fields): void
    {
        $cart = $this->getCart();
        $cart['fields'][(string) $goodId] = $fields;
        $this->setCart($cart);
    }

    /** @return array{lines: list<array>, total: float, goods: array<int, array>} */
    public function resolveCart(): array
    {
        $cart = $this->getCart();
        $lines = [];
        $total = 0.0;
        $goods = [];
        foreach ($cart['items'] as $item) {
            $pack = $this->catalog->packById((int) $item['pack_id']);
            if (!$pack) {
                continue;
            }
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $lineTotal = (float) $pack['price_usd'] * $qty;
            $total += $lineTotal;
            $gid = (int) $pack['good_id'];
            $goods[$gid] = true;
            $lines[] = [
                'pack' => $pack,
                'qty' => $qty,
                'line_total' => $lineTotal,
            ];
        }
        return ['lines' => $lines, 'total' => round($total, 2), 'goods' => $goods];
    }

    public function createOrder(
        string $lang,
        ?int $paymentMethodId,
        ?string $email,
        array $fieldValuesByGood,
    ): int {
        $resolved = $this->resolveCart();
        if ($resolved['lines'] === []) {
            throw new \RuntimeException('Cart is empty');
        }

        $orderNumber = 'ORD-' . strtoupper(bin2hex(random_bytes(4))) . '-' . time();
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO orders (order_number, status, lang, total_usd, payment_method_id, customer_email)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $orderNumber,
                'pending',
                $lang,
                $resolved['total'],
                $paymentMethodId,
                $email,
            ]);
            $orderId = (int) $this->pdo->lastInsertId();

            $itemStmt = $this->pdo->prepare(
                'INSERT INTO order_items (order_id, good_id, pack_id, pack_name_ru, quantity, unit_price_usd, line_total_usd)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($resolved['lines'] as $line) {
                $p = $line['pack'];
                $itemStmt->execute([
                    $orderId,
                    $p['good_id'],
                    $p['id'],
                    $p['name_ru'],
                    $line['qty'],
                    $p['price_usd'],
                    $line['line_total'],
                ]);
            }

            $fieldStmt = $this->pdo->prepare(
                'INSERT INTO order_field_values (order_id, good_id, field_key, field_value) VALUES (?, ?, ?, ?)'
            );
            foreach ($fieldValuesByGood as $goodId => $fields) {
                foreach ($fields as $key => $value) {
                    if ($value === '') {
                        continue;
                    }
                    $fieldStmt->execute([$orderId, (int) $goodId, $key, $value]);
                }
            }

            $this->pdo->commit();
            $this->clearCart();
            return $orderId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function orderById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) {
            return null;
        }
        $items = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $items->execute([$id]);
        $order['items'] = $items->fetchAll();
        $fields = $this->pdo->prepare('SELECT * FROM order_field_values WHERE order_id = ?');
        $fields->execute([$id]);
        $order['fields'] = $fields->fetchAll();
        return $order;
    }

    public function allOrders(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
