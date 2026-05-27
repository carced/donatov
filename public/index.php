<?php

declare(strict_types=1);

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
