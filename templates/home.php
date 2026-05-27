<?php require_once __DIR__ . '/helpers.php'; ?>
<?php if (!empty($refTracked)): ?>
<div class="alert alert-success" role="status"><?= e(t('referral_tracked_banner')) ?></div>
<?php endif; ?>

<section class="hero">
    <h1><?= e(t('hero_title')) ?></h1>
    <p><?= e(t('hero_subtitle')) ?></p>
    <a href="/catalog" class="btn"><?= e(t('nav_catalog')) ?></a>
    <p style="margin-top:20px">
        <a href="/referral" class="btn btn-secondary"><?= e(t('nav_referral')) ?></a>
    </p>
</section>

<?php if ($fx): ?>
<p class="fx-badge"><?= e(t('fx_note')) ?>: <?= e($fx['rate_date']) ?> — 1 USD = <?= number_format((float) $fx['usd_rub'], 2) ?> RUB</p>
<?php endif; ?>

<div class="catalog-grid">
<?php foreach ($goods as $g): ?>
    <a href="/g/<?= e($g['slug']) ?>" class="good-card">
        <img src="<?= e(cover_src($g)) ?>" alt="<?= e(good_name($g)) ?>" loading="lazy" decoding="async">
        <div class="good-card-body">
            <h3><?= e(good_name($g)) ?></h3>
            <?php if ($g['instant']): ?><span class="badge"><?= e(t('instant')) ?></span><?php endif; ?>
        </div>
    </a>
<?php endforeach; ?>
</div>
