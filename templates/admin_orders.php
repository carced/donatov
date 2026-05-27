<?php require_once __DIR__ . '/helpers.php'; ?>
<h1><?= e(t('admin_orders')) ?></h1>
<table class="checkout-table">
    <thead>
        <tr>
            <th>ID</th>
            <th><?= e(t('order_number')) ?></th>
            <th>Status</th>
            <th><?= e(t('total')) ?></th>
            <th>Email</th>
            <th>Created</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
        <tr>
            <td><a href="/order/<?= (int)$o['id'] ?>"><?= (int)$o['id'] ?></a></td>
            <td><?= e($o['order_number']) ?></td>
            <td><?= e($o['status']) ?></td>
            <td><?= price_usd((float)$o['total_usd']) ?></td>
            <td><?= e($o['customer_email'] ?? '—') ?></td>
            <td><?= e($o['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
