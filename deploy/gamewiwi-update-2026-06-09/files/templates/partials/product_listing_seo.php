<?php
/** @var array $listingSeo */
require_once dirname(__DIR__) . '/helpers.php';
$listingSeo = $listingSeo ?? [];
if ($listingSeo === []) {
    return;
}
?>
<section class="product-listing-seo card-panel" aria-labelledby="product-listing-seo-title">
    <h2 id="product-listing-seo-title"><?= e(t('seo_listing_section_title', ['product' => $productName ?? ''])) ?></h2>
    <div class="product-listing-seo__intro"><?= $listingSeo['intro'] ?? '' ?></div>

    <h3 class="product-listing-seo__subhead"><?= e($listingSeo['packs_heading'] ?? '') ?></h3>
    <?= $listingSeo['packs_html'] ?? '' ?>

    <h3 class="product-listing-seo__subhead"><?= e($listingSeo['related_heading'] ?? '') ?></h3>
    <div class="product-listing-seo__related"><?= $listingSeo['related_html'] ?? '' ?></div>
</section>
