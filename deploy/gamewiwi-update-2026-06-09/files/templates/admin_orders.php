<?php require_once __DIR__ . '/helpers.php'; ?>
<h1><?= e(t('admin_orders')) ?></h1>

<section class="card-panel admin-guides" style="margin-bottom:16px;">
    <h2><?= e(t('admin_guides_heading')) ?></h2>
    <p class="text-muted"><?= e(t('admin_guides_help')) ?></p>

    <form method="get" action="/admin" class="admin-guides__toolbar">
        <div class="form-group">
            <label for="guide_game_pick"><?= e(t('admin_guide_game')) ?></label>
            <select id="guide_game_pick" name="guide" class="form-control" onchange="this.form.submit()">
                <?php foreach ($adminGoods as $g): ?>
                <option value="<?= e($g['slug']) ?>" <?= ($editSlug ?? '') === $g['slug'] ? 'selected' : '' ?>>
                    <?= e(good_name($g)) ?> (<?= e($g['slug']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <div class="admin-guides__articles">
        <div class="admin-guides__articles-head">
            <h3><?= e(t('admin_guide_articles_for', ['game' => $editSlug ?? ''])) ?></h3>
            <a href="/admin?guide=<?= e(rawurlencode((string) ($editSlug ?? ''))) ?>&article=new" class="btn btn-secondary btn-sm">
                <?= e(t('admin_guide_new')) ?>
            </a>
        </div>
        <?php if (!empty($gameArticles)): ?>
        <ul class="admin-guides__list">
            <?php foreach ($gameArticles as $aRow): ?>
            <?php
            $aResolved = \App\GameGuide::resolveContent($aRow, 'en') ?? \App\GameGuide::resolveContent($aRow, 'ru');
            $aTitle = $aResolved['title'] ?? (string) $aRow['article_slug'];
            ?>
            <li class="<?= !empty($editGuide['id']) && (int) $editGuide['id'] === (int) $aRow['id'] ? 'is-active' : '' ?>">
                <a href="/admin?guide=<?= e(rawurlencode((string) $editSlug)) ?>&article=<?= (int) $aRow['id'] ?>">
                    <?= e($aTitle) ?>
                </a>
                <span class="text-muted">/<?= e((string) $aRow['article_slug']) ?></span>
                <?php if (empty($aRow['enabled'])): ?><span class="text-muted">(<?= e(t('admin_guide_disabled')) ?>)</span><?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="text-muted"><?= e(t('admin_guide_no_articles')) ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($isNewArticle) || !empty($editGuide) || empty($gameArticles)): ?>
    <form method="post" action="/admin/guides/save" class="admin-guides__form" id="admin-guide-form">
        <input type="hidden" name="good_slug" value="<?= e($editSlug ?? '') ?>">
        <input type="hidden" name="article_id" value="<?= e((string) ($editGuide['id'] ?? '0')) ?>">

        <div class="admin-guides__meta-row">
            <div class="form-group">
                <label for="article_slug"><?= e(t('admin_guide_article_slug')) ?></label>
                <input type="text" id="article_slug" name="article_slug" class="form-control"
                       pattern="[a-z0-9\-]+"
                       value="<?= e($editGuide['article_slug'] ?? '') ?>"
                       placeholder="how-to-buy-crystals">
                <small class="text-muted"><?= e(t('admin_guide_article_slug_help')) ?></small>
            </div>
            <div class="form-group">
                <label for="sort_order"><?= e(t('admin_guide_sort')) ?></label>
                <input type="number" id="sort_order" name="sort_order" class="form-control" min="0"
                       value="<?= e((string) ($editGuide['sort_order'] ?? '0')) ?>">
            </div>
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
                    <label><?= e(t('admin_guide_content')) ?></label>
                    <div class="guide-quill-editor" data-target="content_ru"></div>
                    <textarea id="content_ru" name="content_ru" class="visually-hidden"><?= e($editGuide['content_ru'] ?? '') ?></textarea>
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
                    <label><?= e(t('admin_guide_content')) ?></label>
                    <div class="guide-quill-editor" data-target="content_en"></div>
                    <textarea id="content_en" name="content_en" class="visually-hidden"><?= e($editGuide['content_en'] ?? '') ?></textarea>
                </div>
            </fieldset>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="enabled" value="1" <?= ($editGuide === null || !empty($editGuide['enabled']) || !empty($isNewArticle)) ? 'checked' : '' ?>>
                <?= e(t('admin_guide_enabled')) ?>
            </label>
        </div>

        <?php if (!empty($editGuide) && \App\GameGuide::resolveContent($editGuide, 'en') !== null): ?>
        <p class="text-muted">
            <?= e(t('admin_guide_preview')) ?>:
            <a href="<?= e(\App\GameGuide::guideArticleUrl((string) $editSlug, (string) $editGuide['article_slug'])) ?>" target="_blank" rel="noopener">
                <?= e(\App\GameGuide::guideArticleUrl((string) $editSlug, (string) $editGuide['article_slug'])) ?>
            </a>
        </p>
        <?php endif; ?>

        <div class="admin-guides__actions">
            <button type="submit" class="btn"><?= e(t('admin_guide_save')) ?></button>
        </div>
    </form>
    <?php if (!empty($editGuide['id'])): ?>
    <form method="post" action="/admin/guides/delete" class="admin-guides__delete-form"
          onsubmit="return confirm('<?= e(t('admin_guide_delete_confirm')) ?>')">
        <input type="hidden" name="article_id" value="<?= (int) $editGuide['id'] ?>">
        <input type="hidden" name="good_slug" value="<?= e($editSlug ?? '') ?>">
        <button type="submit" class="btn btn-secondary"><?= e(t('admin_guide_delete')) ?></button>
    </form>
    <?php endif; ?>
    <?php endif; ?>
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

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/admin-guide-editor.css">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="/assets/admin-guide-editor.js" defer></script>
