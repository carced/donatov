<?php require_once __DIR__ . '/helpers.php'; ?>
<?php if (!empty($refTracked)): ?>
<div class="alert alert-success" role="status"><?= e(t('referral_tracked_banner')) ?></div>
<?php endif; ?>

<section class="hero">
    <h1><?= e(t('hero_title')) ?></h1>
    <p><?= e(t('hero_subtitle')) ?></p>
    <div class="hero__actions">
        <a href="/catalog" class="btn"><?= e(t('nav_catalog')) ?></a>
        <a href="/referral" class="btn btn-secondary"><?= e(t('nav_referral')) ?></a>
    </div>
</section>

<section class="seo-blurb card-panel" aria-labelledby="home-seo-heading">
    <h2 id="home-seo-heading" class="visually-hidden"><?= e(t('seo_home_section_title')) ?></h2>
    <p><?= e(t('seo_home_body')) ?></p>
</section>

<?php if ($fx): ?>
<p class="fx-badge"><?= e(t('fx_note')) ?>: <?= e($fx['rate_date']) ?> — 1 USD = <?= number_format((float)$fx['usd_rub'], 2) ?> RUB</p>
<?php endif; ?>

<section aria-labelledby="featured-heading">
    <h2 id="featured-heading"><?= e(t('seo_featured_title')) ?></h2>
    <div class="catalog-grid">
    <?php foreach ($goods as $g): ?>
        <a href="/g/<?= e($g['slug']) ?>" class="good-card" title="<?= e(t('seo_buy_title', ['product' => good_name($g)])) ?>">
            <img src="<?= e(cover_src($g)) ?>" alt="<?= e(t('seo_product_image_alt', ['product' => good_name($g)])) ?>" loading="lazy" decoding="async" width="180" height="180">
            <div class="good-card-body">
                <h3><?= e(good_name($g)) ?></h3>
                <?php if ($g['instant']): ?><span class="badge"><?= e(t('instant')) ?></span><?php endif; ?>
            </div>
        </a>
    <?php endforeach; ?>
    </div>
    <p class="catalog-more"><a href="/catalog"><?= e(t('seo_browse_catalog')) ?> →</a></p>
</section>
