<?php require_once __DIR__ . '/helpers.php'; ?>
<h1><?= e(t('admin_orders')) ?></h1>

<section class="card-panel admin-guides" style="margin-bottom:16px;">
    <h2><?= e(t('admin_guides_heading')) ?></h2>
    <p class="text-muted"><?= e(t('admin_guides_help')) ?></p>

    <?php if (!empty($adminGuides)): ?>
    <ul class="admin-guides__list">
        <?php foreach ($adminGuides as $gRow): ?>
        <li>
            <a href="/admin?guide=<?= e(rawurlencode((string) $gRow['good_slug'])) ?>">
                <?= e((string) $gRow['good_slug']) ?>
            </a>
            <?php if (empty($gRow['enabled'])): ?><span class="text-muted">(<?= e(t('admin_guide_disabled')) ?>)</span><?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <form method="post" action="/admin/guides/save" class="admin-guides__form">
        <div class="form-group">
            <label for="good_slug"><?= e(t('admin_guide_game')) ?></label>
            <select id="good_slug" name="good_slug" class="form-control" onchange="window.location='/admin?guide='+encodeURIComponent(this.value)">
                <?php foreach ($adminGoods as $g): ?>
                <option value="<?= e($g['slug']) ?>" <?= ($editSlug ?? '') === $g['slug'] ? 'selected' : '' ?>>
                    <?= e(good_name($g)) ?> (<?= e($g['slug']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="admin-guides__lang-grid">
            <fieldset class="admin-guides__fieldset">
                <legend><?= e(t('admin_guide_lang_ru')) ?></legend>
                <div class="form-group">
                    <label for="title_ru"><?= e(t('admin_guide_title')) ?></label>
                    <input type="text" id="title_ru" name="title_ru" class="form-control"
                           value="<?= e($editGuide['title_ru'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="meta_description_ru"><?= e(t('admin_guide_meta')) ?></label>
                    <textarea id="meta_description_ru" name="meta_description_ru" rows="2" class="form-control"><?= e($editGuide['meta_description_ru'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="content_ru"><?= e(t('admin_guide_content')) ?></label>
                    <textarea id="content_ru" name="content_ru" rows="14" class="form-control admin-guide-editor"
                              placeholder="<?= e(t('admin_guide_content_hint')) ?>"><?= e($editGuide['content_ru'] ?? '') ?></textarea>
                </div>
            </fieldset>

            <fieldset class="admin-guides__fieldset">
                <legend><?= e(t('admin_guide_lang_en')) ?></legend>
                <div class="form-group">
                    <label for="title_en"><?= e(t('admin_guide_title')) ?></label>
                    <input type="text" id="title_en" name="title_en" class="form-control"
                           value="<?= e($editGuide['title_en'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="meta_description_en"><?= e(t('admin_guide_meta')) ?></label>
                    <textarea id="meta_description_en" name="meta_description_en" rows="2" class="form-control"><?= e($editGuide['meta_description_en'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="content_en"><?= e(t('admin_guide_content')) ?></label>
                    <textarea id="content_en" name="content_en" rows="14" class="form-control admin-guide-editor"
                              placeholder="<?= e(t('admin_guide_content_hint')) ?>"><?= e($editGuide['content_en'] ?? '') ?></textarea>
                </div>
            </fieldset>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="enabled" value="1" <?= ($editGuide === null || !empty($editGuide['enabled'])) ? 'checked' : '' ?>>
                <?= e(t('admin_guide_enabled')) ?>
            </label>
        </div>

        <?php if (!empty($editGuide) && \App\GameGuide::resolveContent($editGuide, 'en') !== null): ?>
        <p class="text-muted">
            <?= e(t('admin_guide_preview')) ?>:
            <a href="/guide/<?= e(rawurlencode((string) ($editSlug ?? ''))) ?>" target="_blank" rel="noopener">
                /guide/<?= e($editSlug ?? '') ?>
            </a>
        </p>
        <?php endif; ?>

        <button type="submit" class="btn"><?= e(t('admin_guide_save')) ?></button>
    </form>
</section>

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
