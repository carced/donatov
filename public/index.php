<?php

declare(strict_types=1);

/**
 * Serve CSS/JS/images directly when the server routes all requests here (common on some hosts).
 */
function servePublicAsset(string $publicDir): bool
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?? '/';
    if (!str_starts_with($path, '/assets/')) {
        return false;
    }

    $file = $publicDir . $path;
    $real = realpath($file);
    $assetsRoot = realpath($publicDir . '/assets');
    if ($real === false || $assetsRoot === false || !str_starts_with($real, $assetsRoot)) {
        http_response_code(404);
        return true;
    }

    $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
    $types = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
    ];
    if (!isset($types[$ext])) {
        http_response_code(404);
        return true;
    }

    header('Content-Type: ' . $types[$ext]);
    header('Cache-Control: public, max-age=86400');
    readfile($real);
    return true;
}

$publicDir = __DIR__;
if (servePublicAsset($publicDir)) {
    exit;
}

session_start();

$root = dirname(__DIR__);
require_once $root . '/src/Config.php';
require_once $root . '/src/Db.php';
require_once $root . '/src/Currency.php';
require_once $root . '/src/Translator.php';
require_once $root . '/src/I18n.php';
require_once $root . '/src/CatalogRepository.php';
require_once $root . '/src/OrderService.php';
require_once $root . '/src/CryptoPayment.php';
require_once $root . '/src/ReferralService.php';
require_once $root . '/src/Seo.php';
require_once $root . '/src/GamePopularity.php';
require_once $root . '/src/ListingTranslator.php';
require_once $root . '/src/Router.php';

use App\Config;
use App\Db;
use App\Router;

Config::load($root);

try {
    $pdo = Db::connect();
    $router = new Router($pdo, $root);
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $e) {
    http_response_code(500);
    if (Config::get('APP_DEBUG') === 'true') {
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    } else {
        echo 'Service temporarily unavailable.';
    }
}
