<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/src/Config.php';
require_once $root . '/src/Db.php';
require_once $root . '/src/Currency.php';

use App\Config;
use App\Currency;
use App\Db;

Config::load($root);
$pdo = Db::connect();

$rate = Currency::fetchCbrUsdRate();
Currency::storeRate($pdo, $rate);
$usdRub = (float) $rate['usd_rub'];
$discount = Currency::discountFactor($pdo);

$stmt = $pdo->query('SELECT id, price_rub_source FROM packs');
$update = $pdo->prepare('UPDATE packs SET price_usd = ? WHERE id = ?');
$count = 0;
while ($row = $stmt->fetch()) {
    $usd = Currency::rubToUsd((float) $row['price_rub_source'], $usdRub, $discount);
    $update->execute([$usd, $row['id']]);
    $count++;
}

echo date('c') . " Updated {$count} pack prices. USD/RUB={$usdRub}\n";
