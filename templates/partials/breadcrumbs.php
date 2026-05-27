<?php
/** @var list<array{label: string, url: ?string}> $seo['breadcrumbs'] */
if (empty($seo['breadcrumbs'])) {
    return;
}
?>
<nav class="breadcrumbs" aria-label="<?= e(t('seo_breadcrumb_label')) ?>">
    <ol class="breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">
        <?php foreach ($seo['breadcrumbs'] as $i => $crumb): ?>
        <li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <?php if ($crumb['url'] !== null): ?>
                <a href="<?= e($crumb['url']) ?>" itemprop="item" title="<?= e($crumb['label']) ?>">
                    <span itemprop="name"><?= e($crumb['label']) ?></span>
                </a>
            <?php else: ?>
                <span itemprop="name" aria-current="page"><?= e($crumb['label']) ?></span>
            <?php endif; ?>
            <meta itemprop="position" content="<?= (int) ($i + 1) ?>">
        </li>
        <?php endforeach; ?>
    </ol>
</nav>
