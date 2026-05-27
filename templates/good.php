<?php require_once __DIR__ . '/helpers.php'; ?>
<article>
    <div class="good-header">
        <div class="good-header-cover">
            <img src="<?= e(cover_src($good)) ?>" alt="<?= e(good_name($good)) ?>">
        </div>
        <div class="good-header-data">
            <h1><?= e(good_name($good)) ?></h1>
            <?php if ($good['instant']): ?><span class="badge"><?= e(t('instant')) ?></span><?php endif; ?>
            <?php if ($content && $content['short_description']): ?>
                <p><?= e(trans_entity('good_content', (string)$good['id'], 'short_description', $content['short_description'])) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($fields): ?>
    <section class="player-info-section">
        <h2><?= e(t('player_info')) ?></h2>
        <p><small><?= e(t('checkout')) ?>: <?= e(t('player_info')) ?></small></p>
    </section>
    <?php endif; ?>

    <h2><?= e(t('buy')) ?></h2>
    <div class="good-pack-groups">
        <?php foreach ($packs as $pack): ?>
        <div class="pack-item">
            <div class="pack-name"><?= e(pack_name($pack)) ?></div>
            <div class="price"><?= price_usd((float)$pack['price_usd']) ?></div>
            <?php if ($pack['in_stock']): ?>
            <form method="post" action="/cart/add">
                <input type="hidden" name="pack_id" value="<?= (int)$pack['id'] ?>">
                <input type="hidden" name="qty" value="1">
                <input type="hidden" name="redirect" value="/checkout">
                <button type="submit" class="btn"><?= e(t('add_to_cart')) ?></button>
            </form>
            <?php else: ?>
            <span><?= e(t('out_of_stock')) ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($content && $content['instruction_html']): ?>
    <section class="instructions" style="margin-top:32px">
        <?= trans_entity('good_content', (string)$good['id'], 'instruction_html', $content['instruction_html']) ?>
    </section>
    <?php endif; ?>
</article>
