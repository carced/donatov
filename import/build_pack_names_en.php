<?php

declare(strict_types=1);

/**
 * Build lang/pack_names_en.json — RU pack title → EN for all lots with Cyrillic.
 * Usage: php import/build_pack_names_en.php
 */

$root = dirname(__DIR__);
require_once $root . '/src/Config.php';
require_once $root . '/src/Db.php';
require_once $root . '/src/Translator.php';

use App\Config;
use App\Db;
use App\Translator;

Config::load($root);

function safe_substr(string $s, int $start, int $len): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($s, $start, $len);
    }
    return substr($s, $start, $len);
}


$pdo = Db::connect();
$outPath = $root . '/lang/pack_names_en.json';

$existing = is_file($outPath)
    ? json_decode((string) file_get_contents($outPath), true)
    : [];
if (!is_array($existing)) {
    $existing = [];
}

$stmt = $pdo->query(
    "SELECT DISTINCT name_ru FROM packs
     WHERE name_ru IS NOT NULL AND TRIM(name_ru) != ''
       AND name_ru REGEXP '[А-Яа-яЁё]'
     ORDER BY name_ru"
);
$names = $stmt->fetchAll(PDO::FETCH_COLUMN);
$total = count($names);
$done = 0;
$added = 0;

echo "Pack titles to translate: {$total}\n";
echo "Already in dictionary: " . count($existing) . "\n";

foreach ($names as $ru) {
    $ru = trim((string) $ru);
    if ($ru === '') {
        continue;
    }
    if (isset($existing[$ru]) && $existing[$ru] !== '' && $existing[$ru] !== $ru) {
        $done++;
        continue;
    }

    $en = Translator::translateFree($ru, 'ru', 'en');
    if ($en === '' || $en === $ru) {
        // Retry once after short pause
        usleep(300000);
        $en = Translator::translateFree($ru, 'ru', 'en');
    }

    $existing[$ru] = $en !== '' ? $en : $ru;
    $added++;
    $done++;

    if ($done % 25 === 0) {
        file_put_contents(
            $outPath,
            json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );
        echo sprintf("[%d/%d] last: %s → %s\n", $done, $total, safe_substr($ru, 0, 40), safe_substr($en, 0, 40));
    }

    usleep(120000);
}

file_put_contents(
    $outPath,
    json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
);

echo "Done. Dictionary size: " . count($existing) . " (+{$added} new)\n";
