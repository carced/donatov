<?php require_once __DIR__ . '/helpers.php'; ?>
<h1><?= e(t('checkout')) ?></h1>
<?php if ($resolved['lines'] === []): ?>
    <p><?= e(t('empty_cart')) ?></p>
    <a href="/catalog" class="btn"><?= e(t('continue_shopping')) ?></a>
<?php else: ?>
<form method="post" action="/checkout">
    <table class="checkout-table">
        <thead>
            <tr>
                <th>Item</th>
                <th><?= e(t('price_usd')) ?></th>
                <th>Qty</th>
                <th><?= e(t('total')) ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($resolved['lines'] as $line): ?>
            <?php $p = $line['pack']; ?>
            <tr>
                <td><?= e(pack_name($p)) ?> <small>(<?= e(good_name(['id' => $p['good_id'], 'name_ru' => $p['good_name']])) ?>)</small></td>
                <td><?= price_usd((float)$p['price_usd']) ?></td>
                <td><?= (int)$line['qty'] ?></td>
                <td><?= price_usd($line['line_total']) ?></td>
                <td><a href="/cart/remove?pack_id=<?= (int)$p['id'] ?>" class="btn btn-secondary"><?= e(t('remove')) ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong><?= e(t('total')) ?></strong></td>
                <td colspan="2"><strong><?= price_usd($resolved['total']) ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <?php foreach ($fieldsByGood as $goodId => $goodFields): ?>
        <?php if ($goodFields): ?>
        <h3><?= e(t('player_info')) ?> #<?= (int)$goodId ?></h3>
        <?php foreach ($goodFields as $field): ?>
        <div class="form-group">
            <label for="f_<?= e($field['field_key']) ?>"><?= e(field_label($field)) ?></label>
            <?php if (($field['field_type'] ?? 'input') === 'select'): ?>
            <select id="f_<?= e($field['field_key']) ?>"
                    class="form-control"
                    name="fields[<?= (int)$goodId ?>][<?= e($field['field_key']) ?>]"
                    <?= !empty($field['required']) ? 'required' : '' ?>>
                <option value=""><?= e(field_placeholder($field) ?: t('select_option')) ?></option>
                <?php foreach (field_options($field) as $option): ?>
                <option value="<?= e($option['id']) ?>"><?= e($option['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php else: ?>
            <input type="<?= e(field_input_type($field)) ?>"
                   id="f_<?= e($field['field_key']) ?>"
                   class="form-control"
                   name="fields[<?= (int)$goodId ?>][<?= e($field['field_key']) ?>]"
                   placeholder="<?= e(field_placeholder($field)) ?>"
                   <?= !empty($field['required']) ? 'required' : '' ?>>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="form-group">
        <label for="email"><?= e(t('email')) ?></label>
        <input type="email" id="email" name="email">
    </div>

    <?php if ($paymentMethods): ?>
    <div class="form-group">
        <label for="payment"><?= e(t('payment_method')) ?></label>
        <select id="payment" name="payment_method_id">
            <?php foreach ($paymentMethods as $pm): ?>
            <option value="<?= (int)$pm['id'] ?>">
                <?= e(trans_entity('payment_method', (string)$pm['id'], 'name', $pm['name_ru'])) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <button type="submit" class="btn"><?= e(t('place_order')) ?></button>
</form>
<?php endif; ?>
