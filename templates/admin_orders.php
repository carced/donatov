<?php require_once __DIR__ . '/helpers.php'; ?>
<h1><?= e(t('admin_orders')) ?></h1>

<section class="card-panel" style="margin-bottom:16px;">
    <h2><?= e(t('admin_adsense_heading')) ?></h2>
    <p class="text-muted"><?= e(t('admin_adsense_help')) ?></p>
    <form method="post" action="/admin/settings/adsense">
        <div class="form-group">
            <label for="adsense_code"><?= e(t('admin_adsense_label')) ?></label>
            <textarea id="adsense_code" name="adsense_code" rows="6" class="form-control" placeholder="<?= e(t('admin_adsense_placeholder')) ?>"><?= e($adsenseCode ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn"><?= e(t('save')) ?></button>
    </form>
</section>
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
