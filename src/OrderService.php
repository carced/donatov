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


    public function createCryptoOrder(
        int $packId,
        string $lang,
        ?string $cryptoId,
        ?string $cryptoAmount,
        ?string $email,
        array $fieldValuesByGood,
        bool $useReferralBalance = false,
    ): int {
        $pack = $this->catalog->packById($packId);
        if (!$pack) {
            throw new \RuntimeException('Invalid pack');
        }

        $lineTotal = (float) $pack['price_usd'];
        $referralAccountId = null;
        $referralCredit = 0.0;
        $status = 'pending';
        $notes = '';

        if ($useReferralBalance) {
            $accountId = ReferralService::loggedInId();
            if ($accountId === null) {
                throw new \RuntimeException('Referral login required');
            }
            $balance = ReferralService::getBalance($this->pdo, $accountId);
            if ($balance < $lineTotal) {
                throw new \RuntimeException('Insufficient referral balance');
            }
            $referralAccountId = $accountId;
            $referralCredit = $lineTotal;
            $status = 'paid';
            $notes = sprintf('Paid with referral balance ($%.2f)', $lineTotal);
        } else {
            $wallet = CryptoPayment::walletById((string) $cryptoId);
            if (!$wallet || $cryptoAmount === null || $cryptoAmount === '') {
                throw new \RuntimeException('Invalid crypto method');
            }
            $notes = sprintf(
                'Crypto: %s (%s) | Amount: %s %s | Address: %s',
                $wallet['name'],
                $wallet['network'],
                $cryptoAmount,
                $wallet['symbol'],
                $wallet['address']
            );
        }

        $orderNumber = 'ORD-' . strtoupper(bin2hex(random_bytes(4))) . '-' . time();
        $this->pdo->beginTransaction();
        try {
            if ($useReferralBalance && $referralAccountId !== null) {
                ReferralService::deductBalance($this->pdo, $referralAccountId, $lineTotal);
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO orders (order_number, status, lang, total_usd, payment_method_id, customer_email, notes, referral_account_id, referral_credit_usd)
                 VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $orderNumber,
                $status,
                $lang,
                $lineTotal,
                $email,
                $notes,
                $referralAccountId,
                $referralCredit,
            ]);
            $orderId = (int) $this->pdo->lastInsertId();

            $this->pdo->prepare(
                'INSERT INTO order_items (order_id, good_id, pack_id, pack_name_ru, quantity, unit_price_usd, line_total_usd)
                 VALUES (?, ?, ?, ?, 1, ?, ?)'
            )->execute([
                $orderId,
                $pack['good_id'],
                $pack['id'],
                $pack['name_ru'],
                $pack['price_usd'],
                $lineTotal,
            ]);

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
            return $orderId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

}
