<?php
$lang = \App\I18n::lang();
$otherLang = $lang === 'ru' ? 'en' : 'ru';
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($siteName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <?php
    $cssDir = dirname(__DIR__) . '/public/assets/css';
    if (is_dir($cssDir)) {
        foreach (glob($cssDir . '/*.css') as $cssFile) {
            echo '<link rel="stylesheet" href="/assets/css/' . e(basename($cssFile)) . '">' . "\n";
        }
    }
    ?>
    <link rel="stylesheet" href="/assets/site.css">
</head>
<body>
    <div id="app">
        <header class="container-fluid">
            <div class="navigation">
                <div class="container">
                    <div class="navigation-wrapper">
                        <div class="navigation-side --logo">
                            <a href="/" title="<?= e(t('nav_home')) ?>">
                                <strong class="brand-logo"><?= e($siteName) ?></strong>
                            </a>
                        </div>
                        <nav class="navigation-side --menu">
                            <a href="/"><?= e(t('nav_home')) ?></a>
                            <a href="/catalog"><?= e(t('nav_catalog')) ?></a>
                            <a href="/checkout"><?= e(t('nav_cart')) ?>
                                <?php if ($cartResolved['total'] > 0): ?>
                                    (<?= price_usd($cartResolved['total']) ?>)
                                <?php endif; ?>
                            </a>
                        </nav>
                        <div class="navigation-side --lang">
                            <a href="<?= e(lang_url($_SERVER['REQUEST_URI'] ?? '/', $otherLang)) ?>" class="lang-switch">
                                <?= $otherLang === 'en' ? 'EN' : 'RU' ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <main class="container main-content">
            <?php if (!empty($flashError)): ?>
                <div class="alert alert-error"><?= e($flashError) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </main>
        <footer class="container site-footer">
            <p>&copy; <?= date('Y') ?> <?= e($siteName) ?>. <?= e(t('fx_note')) ?>.</p>
        </footer>
    </div>
</body>
</html>
