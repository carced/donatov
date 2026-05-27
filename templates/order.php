<?php require_once __DIR__ . '/helpers.php'; ?>
<h1><?= e(t('order_success')) ?></h1>
<p><strong><?= e(t('order_number')) ?>:</strong> <?= e($order['order_number']) ?></p>
<p><strong><?= e(t('total')) ?>:</strong> <?= price_usd((float)$order['total_usd']) ?></p>
<?php if (!empty($order['notes'])): ?>
<p><strong><?= e(t('payment_method')) ?>:</strong><br><?= nl2br(e($order['notes'])) ?></p>
<?php endif; ?>
<p><strong>Status:</strong> <?= e(t('status_pending')) ?></p>
<ul>
<?php foreach ($order['items'] as $item): ?>
    <li><?= e(trans_entity('pack', $item['good_id'] . ':' . $item['pack_id'], 'name', $item['pack_name_ru'])) ?>
        × <?= (int)$item['quantity'] ?> — <?= price_usd((float)$item['line_total_usd']) ?></li>
<?php endforeach; ?>
</ul>
<a href="/catalog" class="btn"><?= e(t('continue_shopping')) ?></a>
