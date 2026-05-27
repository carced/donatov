<?php
require_once __DIR__ . '/helpers.php';
$lang = \App\I18n::lang();
$otherLang = $lang === 'ru' ? 'en' : 'ru';
$cartTotal = $cartResolved['total'] ?? 0;
$seo = $seo ?? [
    'title' => $siteName ?? 'GameStore',
    'description' => '',
    'canonical' => \App\Seo::absoluteUrl('/'),
    'robots' => 'index, follow',
    'og_type' => 'website',
    'breadcrumbs' => [],
    'json_ld' => [],
];
$pageTitle = $seo['title'];
$metaDescription = $seo['description'];
$canonicalUrl = $seo['canonical'];
$robotsMeta = $seo['robots'];
$ogType = $seo['og_type'];
$alternateLangUrl = lang_url(\App\Seo::currentPath(), $otherLang);
$alternateCanonical = \App\Seo::absoluteUrl(\App\Seo::currentPath()) . (str_contains(\App\Seo::currentPath(), '?') ? '&' : '?') . 'lang=' . $otherLang;
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f0f14">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="robots" content="<?= e($robotsMeta) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <link rel="alternate" hreflang="<?= e($lang) ?>" href="<?= e($canonicalUrl) ?>">
    <link rel="alternate" hreflang="<?= e($otherLang) ?>" href="<?= e($alternateCanonical) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= e(\App\Seo::absoluteUrl(\App\Seo::currentPath())) ?>">

    <meta property="og:type" content="<?= e($ogType) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:locale" content="<?= $lang === 'ru' ? 'ru_RU' : 'en_US' ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">

    <style>
      :root{--bg:#0f0f14;--text:#f4f4f8;--accent:#8b5cf6;--card:#1e1e28;--border:rgba(255,255,255,.1)}
      *,*::before,*::after{box-sizing:border-box}
      html{color-scheme:dark}
      body{margin:0;font-family:"Plus Jakarta Sans",system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.5}
      #app.app-shell{min-height:100vh;background:var(--bg);color:var(--text)}
      .site-header{position:sticky;top:0;z-index:100;background:rgba(15,15,20,.92);border-bottom:1px solid var(--border);backdrop-filter:blur(10px)}
      .container{max-width:1200px;margin:0 auto;padding:0 20px}
      .site-header__inner{display:flex;align-items:center;justify-content:space-between;min-height:64px;gap:12px}
      .site-brand{display:flex;align-items:center;gap:10px;color:var(--text);text-decoration:none;font-weight:700}
      .site-brand__mark{width:32px;height:32px;border-radius:10px;background:linear-gradient(135deg,#8b5cf6,#06b6d4)}
      .site-nav{display:flex;flex-wrap:wrap;gap:4px;align-items:center}
      .site-nav__link{color:#9ca3b8;text-decoration:none;padding:8px 12px;border-radius:8px;font-weight:500}
      .catalog-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px}
      .hero{background:linear-gradient(135deg,#7c3aed,#06b6d4);color:#fff;padding:40px 24px;border-radius:14px;text-align:center;margin-bottom:24px}
      .btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 18px;border-radius:10px;background:linear-gradient(135deg,#8b5cf6,#06b6d4);color:#fff!important;text-decoration:none;font-weight:600;border:none;cursor:pointer}
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php if (!empty($isGoodPage)): ?>
    <link rel="stylesheet" href="<?= e(asset('assets/css/vendor.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/good-page.css')) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('assets/site.css')) ?>">

    <?php foreach ($seo['json_ld'] ?? [] as $block): ?>
    <script type="application/ld+json"><?= json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endforeach; ?>
</head>
<body>
    <div id="app" class="app-shell">
        <header class="site-header">
            <div class="container site-header__inner">
                <a href="/" class="site-brand" title="<?= e(t('seo_link_home_title')) ?>">
                    <span class="site-brand__mark" aria-hidden="true"></span>
                    <span class="site-brand__text"><?= e($siteName) ?></span>
                </a>

                <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="site-nav">
                    <span class="nav-toggle__bar"></span>
                    <span class="nav-toggle__bar"></span>
                    <span class="nav-toggle__bar"></span>
                    <span class="visually-hidden">Menu</span>
                </button>

                <nav class="site-nav" id="site-nav" aria-label="<?= e(t('seo_main_nav')) ?>">
                    <a href="/" class="site-nav__link"><?= e(t('nav_home')) ?></a>
                    <a href="/catalog" class="site-nav__link"><?= e(t('nav_catalog')) ?></a>
                    <a href="/referral" class="site-nav__link"><?= e(t('nav_referral')) ?></a>
                    <a href="/checkout" class="site-nav__link site-nav__link--cart">
                        <?= e(t('nav_cart')) ?>
                        <?php if ($cartTotal > 0): ?>
                            <span class="cart-pill"><?= price_usd($cartTotal) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= e(lang_url(\App\Seo::currentPath(), $otherLang)) ?>" class="site-nav__link site-nav__link--lang" hreflang="<?= e($otherLang) ?>">
                        <?= $otherLang === 'en' ? 'EN' : 'RU' ?>
                    </a>
                </nav>
            </div>
        </header>

        <main class="main-content<?= !empty($isGoodPage) ? ' main-content--good' : '' ?>" id="main-content">
            <div class="container">
                <?php include __DIR__ . '/partials/breadcrumbs.php'; ?>
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
            <div class="container">
                <nav class="footer-nav" aria-label="<?= e(t('seo_footer_nav')) ?>">
                    <div class="footer-nav__grid">
                        <?php foreach ($footerLinks ?? [] as $link): ?>
                        <a href="<?= e($link['href']) ?>" title="<?= e($link['title'] ?? $link['label']) ?>"><?= e($link['label']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </nav>
                <div class="site-footer__inner">
                    <p>&copy; <?= date('Y') ?> <?= e($siteName) ?></p>
                    <p class="site-footer__note"><?= e(t('fx_note')) ?> · <a href="/sitemap.xml"><?= e(t('seo_sitemap')) ?></a></p>
                </div>
            </div>
        </footer>
    </div>
    <script src="<?= e(asset('assets/site.js')) ?>" defer></script>
</body>
</html>
