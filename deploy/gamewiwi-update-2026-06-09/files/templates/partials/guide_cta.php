<?php
/** @var string $productName */
/** @var string $guideSlug */
/** @var string $guideCtaVariant inline|hero|compact */
$guideCtaVariant = $guideCtaVariant ?? 'inline';
$storeUrl = \App\GameGuide::storeUrl($guideSlug);
$referralUrl = '/referral';
$isHero = $guideCtaVariant === 'hero';
$isCompact = $guideCtaVariant === 'compact';
?>
<aside class="guide-cta guide-cta--<?= e($guideCtaVariant) ?>" aria-label="<?= e(t('guide_cta_aria', ['product' => $productName])) ?>">
    <div class="guide-cta__inner">
        <?php if ($isHero): ?>
        <p class="guide-cta__eyebrow"><?= e(t('guide_cta_eyebrow')) ?></p>
        <?php endif; ?>
        <p class="guide-cta__title">
            <?= e(t('guide_cta_title', ['product' => $productName])) ?>
        </p>
        <?php if (!$isCompact): ?>
        <p class="guide-cta__text"><?= e(t('guide_cta_text', ['product' => $productName])) ?></p>
        <?php endif; ?>
        <div class="guide-cta__actions">
            <a href="<?= e($storeUrl) ?>" class="btn btn-primary guide-cta__btn guide-cta__btn--buy">
                <?= e(t('guide_cta_buy', ['product' => $productName])) ?>
            </a>
            <a href="<?= e($referralUrl) ?>" class="btn btn-secondary guide-cta__btn guide-cta__btn--free">
                <?= e(t('guide_cta_free', ['product' => $productName])) ?>
            </a>
        </div>
    </div>
</aside>
