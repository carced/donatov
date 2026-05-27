<?php
require_once __DIR__ . '/helpers.php';
$lang = \App\I18n::lang();
$otherLang = $lang === 'ru' ? 'en' : 'ru';
$cartTotal = $cartResolved['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f0f14">
    <title><?= e($siteName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/vendor.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/site.css">
    <?php if (!empty($isGoodPage)): ?>
    <link rel="stylesheet" href="/assets/good-page.css">
    <?php endif; ?>
</head>
<body>
    <div id="app" class="app-shell">
        <header class="site-header">
            <div class="container site-header__inner">
                <a href="/" class="site-brand" title="<?= e(t('nav_home')) ?>">
                    <span class="site-brand__mark"></span>
                    <span class="site-brand__text"><?= e($siteName) ?></span>
                </a>

                <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="site-nav">
                    <span class="nav-toggle__bar"></span>
                    <span class="nav-toggle__bar"></span>
                    <span class="nav-toggle__bar"></span>
                    <span class="visually-hidden">Menu</span>
                </button>

                <nav class="site-nav" id="site-nav">
                    <a href="/" class="site-nav__link"><?= e(t('nav_home')) ?></a>
                    <a href="/catalog" class="site-nav__link"><?= e(t('nav_catalog')) ?></a>
                    <a href="/referral" class="site-nav__link"><?= e(t('nav_referral')) ?></a>
                    <a href="/checkout" class="site-nav__link site-nav__link--cart">
                        <?= e(t('nav_cart')) ?>
                        <?php if ($cartTotal > 0): ?>
                            <span class="cart-pill"><?= price_usd($cartTotal) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= e(lang_url($_SERVER['REQUEST_URI'] ?? '/', $otherLang)) ?>" class="site-nav__link site-nav__link--lang">
                        <?= $otherLang === 'en' ? 'EN' : 'RU' ?>
                    </a>
                </nav>
            </div>
        </header>

        <main class="main-content<?= !empty($isGoodPage) ? ' main-content--good' : '' ?>">
            <div class="container">
                <?php if (!empty($flashSuccess)): ?>
                    <div class="alert alert-success" role="status"><?= e($flashSuccess) ?></div>
                <?php endif; ?>
                <?php if (!empty($flashError)): ?>
                    <div class="alert alert-error" role="alert"><?= e($flashError) ?></div>
                <?php endif; ?>
                <?= $content ?>
            </div>
        </main>

        <footer class="site-footer">
            <div class="container site-footer__inner">
                <p>&copy; <?= date('Y') ?> <?= e($siteName) ?></p>
                <p class="site-footer__note"><?= e(t('fx_note')) ?></p>
            </div>
        </footer>
    </div>
    <script src="/assets/site.js" defer></script>
</body>
</html>
