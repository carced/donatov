<?php

declare(strict_types=1);

$publicDir = __DIR__;
$bootstrapRoot = is_file(__DIR__ . '/src/AppPaths.php') ? __DIR__ : dirname(__DIR__);
require_once $bootstrapRoot . '/src/AppPaths.php';
require_once $bootstrapRoot . '/src/Installer.php';

use App\AppPaths;
use App\Installer;

Installer::boot(__DIR__);
$root = Installer::appRoot();

if (AppPaths::isInstalled($root) && ($_GET['force'] ?? '') !== '1') {
    header('Location: /');
    exit;
}

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = Installer::install($_POST);
}

$checks = Installer::requirements();
$allOk = Installer::allRequirementsMet();
$hasCatalog = is_file($root . '/data/catalog.json');
$defaultUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'gamewiwi.com');

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install GameWiwi.com</title>
    <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
            background: #0f0f14;
            color: #f4f4f8;
            line-height: 1.5;
            padding: 24px 16px 48px;
        }
        .wrap { max-width: 520px; margin: 0 auto; }
        h1 { font-size: 1.5rem; margin: 0 0 8px; }
        .logo { max-width: 200px; height: auto; margin-bottom: 20px; }
        .card {
            background: #1e1e28;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px;
        }
        label { display: block; font-size: 0.85rem; margin: 12px 0 4px; color: #9ca3b8; }
        input[type=text], input[type=password], input[type=url] {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.15);
            background: #18181f;
            color: #fff;
            font-size: 16px;
        }
        .btn {
            display: block;
            width: 100%;
            margin-top: 16px;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .ok { color: #34d399; }
        .bad { color: #f472b6; }
        .alert { padding: 12px; border-radius: 10px; margin-bottom: 16px; }
        .alert-error { background: rgba(244,114,182,0.15); border: 1px solid #f472b6; }
        .alert-success { background: rgba(52,211,153,0.15); border: 1px solid #34d399; }
        ul.checks { list-style: none; padding: 0; margin: 0; font-size: 0.9rem; }
        ul.checks li { padding: 4px 0; }
        .hint { font-size: 0.85rem; color: #9ca3b8; margin-top: 8px; }
        code { background: #18181f; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="wrap">
    <img src="/assets/logo-gamewiwi.svg" alt="GameWiwi.com" class="logo" width="200" height="40">
    <h1>Install GameWiwi.com</h1>
    <p class="hint">Upload all files to <code>public_html</code>, then open this page once. Delete <code>install.php</code> after setup.</p>

    <?php if ($result !== null && !$result['ok']): ?>
        <div class="alert alert-error"><?= h($result['error'] ?? 'Install failed') ?></div>
    <?php endif; ?>

    <?php if ($result !== null && $result['ok']): ?>
        <div class="alert alert-success">
            <strong>Installation complete.</strong>
            <?php if (!empty($result['import'])): ?>
                <p><?= h($result['import']) ?></p>
            <?php endif; ?>
            <p>Delete <code>public/install.php</code> and <code>install.php</code> for security.</p>
            <p><a href="/" style="color:#06b6d4">Open your store →</a></p>
        </div>
    <?php else: ?>

    <div class="card">
        <strong>Server checks</strong>
        <ul class="checks">
            <?php foreach ($checks as $c): ?>
            <li class="<?= $c['ok'] ? 'ok' : 'bad' ?>">
                <?= $c['ok'] ? '✓' : '✗' ?> <?= h($c['label']) ?>
                <?php if (!$c['ok']): ?> <span class="hint">(<?= h($c['got']) ?>)</span><?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <form method="post" class="card">
        <label>Site URL</label>
        <input type="url" name="app_url" required placeholder="https://gamewiwi.com" value="<?= h($_POST['app_url'] ?? $defaultUrl) ?>">

        <label>Database host</label>
        <input type="text" name="db_host" value="<?= h($_POST['db_host'] ?? '127.0.0.1') ?>">

        <label>Database port</label>
        <input type="text" name="db_port" value="<?= h($_POST['db_port'] ?? '3306') ?>">

        <label>Database name</label>
        <input type="text" name="db_name" required value="<?= h($_POST['db_name'] ?? '') ?>" placeholder="gamewiwi_db">

        <label>Database user</label>
        <input type="text" name="db_user" required value="<?= h($_POST['db_user'] ?? '') ?>">

        <label>Database password</label>
        <input type="password" name="db_pass" value="<?= h($_POST['db_pass'] ?? '') ?>">

        <label>Admin password</label>
        <input type="password" name="admin_password" required autocomplete="new-password">

        <?php if ($hasCatalog): ?>
        <label style="display:flex;align-items:center;gap:8px;margin-top:16px;">
            <input type="checkbox" name="import_catalog" value="1" checked>
            Import game catalog from bundled data (recommended)
        </label>
        <?php else: ?>
        <p class="hint">No <code>data/catalog.json</code> found — import catalog later via SSH: <code>php import/import_to_mysql.php</code></p>
        <?php endif; ?>

        <button type="submit" class="btn" <?= $allOk ? '' : 'disabled' ?>>Install now</button>
    </form>

    <p class="hint"><strong>Document root:</strong> point your domain to the <code>public</code> folder, or upload the full project into <code>public_html</code> and use the root <code>index.php</code>.</p>
    <?php endif; ?>
</div>
</body>
</html>
