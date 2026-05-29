<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/src/Config.php';
require_once $root . '/src/Db.php';
require_once $root . '/src/Currency.php';
require_once $root . '/src/Translator.php';

use App\Config;
use App\Currency;
use App\Db;
use App\Translator;


function isPasswordFieldKey(string $key, string $label = ''): bool
{
    $key = strtolower(trim($key));
    if ($key === 'password' || $key === 'pass' || str_contains($key, 'password')) {
        return true;
    }
    $label = strtolower($label);

    return str_contains($label, 'парол') || str_contains($label, 'password');
}

function normalizeDatetime(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }
    $ts = strtotime($value);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

Config::load($root);
$pdo = Db::connect();

$dataDir = $root . '/data';
$catalogFile = $dataDir . '/catalog.json';
if (!is_file($catalogFile)) {
    fwrite(STDERR, "Run scraper first: python3 scraper/scrape.py\n");
    exit(1);
}

$discount = Currency::discountFactor($pdo);
$rate = Currency::fetchCbrUsdRate();
Currency::storeRate($pdo, $rate);
$usdRub = (float) $rate['usd_rub'];
echo "CBR USD/RUB: {$usdRub}, discount: {$discount}\n";

$catalog = json_decode(file_get_contents($catalogFile), true);
$categories = $catalog['data']['categories'] ?? [];

$catStmt = $pdo->prepare(
    'INSERT INTO categories (id, name_ru, icon, sort_order) VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE name_ru = VALUES(name_ru), icon = VALUES(icon)'
);
$sort = 0;
foreach ($categories as $cat) {
    $catStmt->execute([$cat['id'], $cat['name'], $cat['icon'] ?? null, $sort++]);
    upsertTranslation($pdo, 'category', $cat['id'], 'name', $cat['name']);
    $tagSort = 0;
    $tagStmt = $pdo->prepare(
        'INSERT INTO tags (id, category_id, name_ru, icon) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE name_ru = VALUES(name_ru)'
    );
    foreach ($cat['tags'] ?? [] as $tag) {
        $tagStmt->execute([$tag['id'], $cat['id'], $tag['name'], $tag['icon'] ?? null]);
    }
}

function asText(mixed $value): string
{
    if (is_string($value)) {
        return $value;
    }
    if (is_array($value)) {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
    return (string) $value;
}

function dictEn(string $ruText): ?string
{
    static $dict = null;
    if ($dict === null) {
        $path = dirname(__DIR__) . '/lang/dict_en.json';
        $dict = is_file($path) ? json_decode(file_get_contents($path), true) : [];
    }
    return $dict[$ruText] ?? null;
}

function upsertTranslation(PDO $pdo, string $type, string $entityId, string $field, mixed $ruText, string $lang = 'en'): void
{
    $ruText = asText($ruText);
    if ($lang === 'ru' || trim($ruText) === '') {
        return;
    }
    $hash = hash('sha256', $ruText);
    $provider = Config::get('TRANSLATION_PROVIDER', 'none') ?? 'none';
    if ($provider === 'none') {
        $dictText = dictEn($ruText);
        if ($dictText === null) {
            return;
        }
        $translated = $dictText;
    } else {
    $check = $pdo->prepare(
        'SELECT id, source_hash FROM translations
         WHERE entity_type = ? AND entity_id = ? AND field_name = ? AND lang = ?'
    );
    $check->execute([$type, $entityId, $field, $lang]);
    $existing = $check->fetch();
    if ($existing && $existing['source_hash'] === $hash) {
        return;
    }
    $translated = Translator::translate($ruText, 'ru', $lang);
    }
    $stmt = $pdo->prepare(
        'INSERT INTO translations (entity_type, entity_id, field_name, lang, text_value, source_hash)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE text_value = VALUES(text_value), source_hash = VALUES(source_hash)'
    );
    $stmt->execute([$type, $entityId, $field, $lang, $translated, $hash]);
}

$goodStmt = $pdo->prepare(
    'INSERT INTO goods (id, slug, name_ru, category_id, type, cover_url, cover_path, currency_name_ru, instant, enabled, region, cashback, meta_json, sort_order)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       name_ru = VALUES(name_ru), category_id = VALUES(category_id), cover_url = VALUES(cover_url),
       cover_path = VALUES(cover_path), currency_name_ru = VALUES(currency_name_ru),
       instant = VALUES(instant), enabled = VALUES(enabled), meta_json = VALUES(meta_json)'
);

$goodsImported = 0;
$packsImported = 0;
$catalogItems = $catalog['data']['catalog'] ?? [];
$order = 0;

foreach ($catalogItems as $item) {
    $gid = (int) $item['id'];
    $goodFile = $dataDir . '/goods/' . $gid . '.json';
    if (!is_file($goodFile)) {
        echo "SKIP missing good file: {$gid}\n";
        continue;
    }
    $resource = json_decode(file_get_contents($goodFile), true);
    $good = $resource['good'] ?? [];
    $data = $resource['data'] ?? [];
    $slug = $good['slug'] ?? trim($item['url'] ?? '', '/');
    $coverUrl = $good['cover'] ?? $item['cover'] ?? null;
    $coverPath = null;
    if ($coverUrl) {
        $fname = basename(parse_url($coverUrl, PHP_URL_PATH) ?: '');
        $local = $dataDir . '/assets/covers/' . $fname;
        if (is_file($local)) {
            $destDir = $root . '/public/assets/covers';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $dest = $destDir . '/' . $fname;
            if (!is_file($dest)) {
                copy($local, $dest);
            }
            $coverPath = '/assets/covers/' . $fname;
        }
    }

    $goodStmt->execute([
        $gid,
        $slug,
        $good['name'] ?? $item['name'],
        $item['category'] ?? 'games',
        $good['type'] ?? 'pack',
        $coverUrl,
        $coverPath,
        is_array($item['cur'] ?? null) ? ($item['cur']['name'] ?? null) : null,
        !empty($item['instant']) ? 1 : 0,
        !empty($good['enabled']) ? 1 : 0,
        $good['region'] ?? null,
        $good['cashback'] ?? null,
        json_encode(['source_url' => $item['url'] ?? null]),
        $order++,
    ]);
    upsertTranslation($pdo, 'good', (string) $gid, 'name', $good['name'] ?? $item['name']);

    $pdo->prepare('DELETE FROM good_tags WHERE good_id = ?')->execute([$gid]);
    $tagIns = $pdo->prepare('INSERT IGNORE INTO good_tags (good_id, tag_id, category_id) VALUES (?, ?, ?)');
    foreach ($item['tags'] ?? [] as $tagId) {
        $tagIns->execute([$gid, $tagId, $item['category']]);
    }

    $contentStmt = $pdo->prepare(
        'INSERT INTO good_content (good_id, short_description, description_html, instruction_html, warning_text, promo_text, uid_help, meta_title, meta_description, advantages_json)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           short_description = VALUES(short_description), description_html = VALUES(description_html),
           instruction_html = VALUES(instruction_html), warning_text = VALUES(warning_text),
           promo_text = VALUES(promo_text), uid_help = VALUES(uid_help),
           meta_title = VALUES(meta_title), meta_description = VALUES(meta_description),
           advantages_json = VALUES(advantages_json)'
    );
    $contentStmt->execute([
        $gid,
        $data['short_description'] ?? null,
        $data['description_html'] ?? null,
        $data['instruction_html'] ?? null,
        $data['warning_text'] ?? null,
        $data['promo_text'] ?? null,
        $data['uid_help'] ?? null,
        $data['meta_title'] ?? null,
        $data['meta_description'] ?? null,
        isset($data['advantages']) ? json_encode($data['advantages']) : null,
    ]);
    foreach (['short_description', 'description_html', 'instruction_html', 'warning_text', 'promo_text', 'uid_help', 'meta_title', 'meta_description'] as $f) {
        if (!empty($data[$f])) {
            upsertTranslation($pdo, 'good_content', (string) $gid, $f, $data[$f]);
        }
    }

    $pdo->prepare('DELETE FROM good_fields WHERE good_id = ?')->execute([$gid]);
    $schemaFields = $resource['config']['schema_fields'] ?? [];
    $fieldStmt = $pdo->prepare(
        'INSERT INTO good_fields (good_id, field_key, label_ru, field_type, input_type, placeholder, required, validation_json, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $fs = 0;
    foreach ($schemaFields as $key => $field) {
        $fieldKey = (string) ($field['model'] ?? $key);
        $fieldLabel = (string) ($field['label'] ?? $key);
        if (isPasswordFieldKey($fieldKey, $fieldLabel)) {
            continue;
        }
        $fieldStmt->execute([
            $gid,
            $fieldKey,
            $fieldLabel,
            $field['type'] ?? 'input',
            $field['inputType'] ?? 'string',
            $field['placeholder'] ?? null,
            !empty($field['required']) ? 1 : 0,
            json_encode(array_diff_key($field, ['label' => 1])),
            $fs++,
        ]);
        upsertTranslation($pdo, 'good_field', $gid . ':' . $fieldKey, 'label', $fieldLabel);
    }

    $pdo->prepare('DELETE FROM pack_groups WHERE good_id = ?')->execute([$gid]);
    $groupMap = [];
    $groupStmt = $pdo->prepare(
        'INSERT INTO pack_groups (good_id, source_group_id, name_ru) VALUES (?, ?, ?)'
    );
    foreach ($data['pack_groups'] ?? [] as $pg) {
        $groupStmt->execute([$gid, $pg['id'] ?? 0, $pg['name'] ?? null]);
        $groupMap[(int) ($pg['id'] ?? 0)] = (int) $pdo->lastInsertId();
    }

    $pdo->prepare('DELETE FROM packs WHERE good_id = ?')->execute([$gid]);
    $packStmt = $pdo->prepare(
        'INSERT INTO packs (good_id, source_pack_id, name_ru, group_id, price_rub_source, price_old_rub, price_usd, cover_url, in_stock, expired_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($resource['packs'] ?? [] as $pack) {
        $rub = (float) ($pack['price'] ?? 0);
        $usd = Currency::rubToUsd($rub, $usdRub, $discount);
        $groupId = null;
        if (isset($pack['group']) && isset($groupMap[(int) $pack['group']])) {
            $groupId = $groupMap[(int) $pack['group']];
        }
        $packStmt->execute([
            $gid,
            $pack['id'],
            $pack['name'],
            $groupId,
            $rub,
            isset($pack['price_old']) ? (float) $pack['price_old'] : null,
            $usd,
            $pack['cover'] ?? null,
            !empty($pack['in_stock']) ? 1 : 0,
            normalizeDatetime($pack['expired_at'] ?? null),
        ]);
        upsertTranslation($pdo, 'pack', $gid . ':' . $pack['id'], 'name', $pack['name']);
        $packsImported++;
    }
    $goodsImported++;
    if ($goodsImported % 25 === 0) {
        echo "Imported {$goodsImported} goods...\n";
    }
}

$payFile = $dataDir . '/paymethods.json';
if (is_file($payFile)) {
    $payData = json_decode(file_get_contents($payFile), true);
    $payStmt = $pdo->prepare(
        'INSERT INTO payment_methods (id, name_ru, cover_data, fee, meta_json, enabled)
         VALUES (?, ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE name_ru = VALUES(name_ru), fee = VALUES(fee), meta_json = VALUES(meta_json)'
    );
    foreach ($payData['data'] ?? [] as $pm) {
        $payStmt->execute([
            $pm['id'],
            $pm['name'],
            $pm['cover'] ?? null,
            isset($pm['fee']) ? (float) $pm['fee'] : null,
            json_encode($pm['meta'] ?? []),
        ]);
        upsertTranslation($pdo, 'payment_method', (string) $pm['id'], 'name', $pm['name']);
    }
}

$cssSrc = $dataDir . '/assets/css';
$cssDest = $root . '/public/assets/css';
if (is_dir($cssSrc)) {
    if (!is_dir($cssDest)) {
        mkdir($cssDest, 0755, true);
    }
    foreach (['vendor.css', 'app.css'] as $cssName) {
        $src = $cssSrc . '/' . $cssName;
        if (!is_file($src)) {
            foreach (glob($cssSrc . '/*' . str_replace('.css', '', $cssName) . '*') as $alt) {
                $src = $alt;
                break;
            }
        }
        if (is_file($src)) {
            copy($src, $cssDest . '/' . $cssName);
        }
    }
}

echo "Done: {$goodsImported} goods, {$packsImported} packs\n";
