<?php require_once __DIR__ . '/helpers.php';
$sort = $sort ?? 'popular';
?>
<?php if (!empty($refTracked)): ?>
<div class="alert alert-success" role="status"><?= e(t('referral_tracked_banner')) ?></div>
<?php endif; ?>

<section class="hero hero--referral hero--referral-hype" aria-labelledby="referral-hero-title">
    <div class="hero--referral__bg" aria-hidden="true">
        <span class="hero--referral__orb hero--referral__orb--1"></span>
        <span class="hero--referral__orb hero--referral__orb--2"></span>
        <span class="hero--referral__orb hero--referral__orb--3"></span>
        <span class="hero--referral__shine"></span>
    </div>
    <div class="hero--referral__inner">
        <div class="hero--referral__badge">
            <span class="hero--referral__badge-pulse" aria-hidden="true"></span>
            <span class="hero--referral__badge-text"><?= e(t('btn_free')) ?> · <?= e(t('nav_referral')) ?></span>
        </div>
        <h1 id="referral-hero-title" class="hero--referral__title"><?= e(t('hero_title')) ?></h1>
        <p class="hero--referral__lead"><?= e(t('hero_subtitle')) ?></p>
        <div class="hero--referral__stats" role="list">
            <div class="hero-stat-pill" role="listitem">
                <span class="hero-stat-pill__value">$0.10</span>
                <span class="hero-stat-pill__label"><?= e(t('referral_per_click')) ?></span>
            </div>
            <div class="hero-stat-pill" role="listitem">
                <span class="hero-stat-pill__value">⚡</span>
                <span class="hero-stat-pill__label"><?= e(t('instant')) ?></span>
            </div>
            <div class="hero-stat-pill" role="listitem">
                <span class="hero-stat-pill__value">0₽</span>
                <span class="hero-stat-pill__label"><?= e(t('hero_hype_signup')) ?></span>
            </div>
        </div>
        <ul class="hero__perks hero__perks--hype">
            <li><?= e(t('referral_free_banner')) ?></li>
            <li><?= e(t('referral_benefit_earn', ['amount' => '$0.10'])) ?></li>
            <li><?= e(t('referral_benefit_buy')) ?></li>
        </ul>
        <div class="hero__actions hero__actions--hype">
            <a href="/referral" class="btn btn-referral-cta btn-referral-cta--hype" data-referral-slide>
                <span><?= e(t('hero_cta_referral')) ?></span>
            </a>
            <a href="/catalog" class="btn btn-secondary btn-secondary--hype"><?= e(t('hero_cta_catalog')) ?></a>
        </div>
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
