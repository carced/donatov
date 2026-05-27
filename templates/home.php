<?php require_once __DIR__ . '/helpers.php';
$sort = $sort ?? 'popular';
?>
<?php if (!empty($refTracked)): ?>
<div class="alert alert-success" role="status"><?= e(t('referral_tracked_banner')) ?></div>
<?php endif; ?>

<section class="hero hero--referral hero--referral-unified">
    <div class="hero--referral__badge"><?= e(t('btn_free')) ?> · <?= e(t('nav_referral')) ?></div>
    <h1><?= e(t('hero_title')) ?></h1>
    <p class="hero__lead"><?= e(t('hero_subtitle')) ?></p>
    <ul class="hero__perks">
        <li><?= e(t('referral_free_banner')) ?></li>
        <li><?= e(t('referral_benefit_earn', ['amount' => '$0.10'])) ?></li>
        <li><?= e(t('referral_benefit_buy')) ?></li>
    </ul>
    <div class="hero__actions">
        <a href="/referral" class="btn btn-referral-cta" data-referral-slide><?= e(t('hero_cta_referral')) ?></a>
        <a href="/catalog" class="btn btn-secondary"><?= e(t('hero_cta_catalog')) ?></a>
    </div>
</section>

<?php if ($fx): ?>
<p class="fx-badge"><?= e(t('fx_note')) ?>: <?= e($fx['rate_date']) ?> — 1 USD = <?= number_format((float) $fx['usd_rub'], 2) ?> RUB</p>
<?php endif; ?>

<section class="home-listings" aria-labelledby="featured-heading">
    <div class="home-listings__head">
        <h2 id="featured-heading"><?= e(t('home_listings_title')) ?></h2>
        <form class="home-sort" method="get" action="/">
            <?php if (!empty($_GET['lang'])): ?>
            <input type="hidden" name="lang" value="<?= e((string) $_GET['lang']) ?>">
            <?php endif; ?>
            <label for="home-sort-select" class="home-sort__label"><?= e(t('sort_label')) ?></label>
            <select id="home-sort-select" name="sort" class="form-control home-sort__select" onchange="this.form.submit()">
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>><?= e(t('sort_popular')) ?></option>
                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>><?= e(t('sort_name_asc')) ?></option>
                <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>><?= e(t('sort_name_desc')) ?></option>
            </select>
        </form>
    </div>

    <?php include __DIR__ . '/partials/catalog_search.php'; ?>

    <div class="catalog-grid catalog-grid--cards" data-catalog-grid>
        <?php foreach ($goods as $g): ?>
            <?php include __DIR__ . '/partials/good_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <p class="catalog-more"><a href="/catalog"><?= e(t('seo_browse_catalog')) ?> →</a></p>
</section>
