<?php

declare(strict_types=1);

use App\Currency;
use App\I18n;

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function good_name(array $good): string
{
    return \App\GoodName::display($good);
}

function pack_name(array $pack): string
{
    $name = trans_entity('pack', $pack['good_id'] . ':' . $pack['source_pack_id'], 'name', $pack['name_ru']);
    $name = localize_label($name, 'pack');

    return \App\ListingTranslator::toEnglish($name);
}

function field_label(array $field): string
{
    $label = trans_entity('good_field', $field['good_id'] . ':' . $field['field_key'], 'label', $field['label_ru']);
    $byKey = label_for_field_key((string) ($field['field_key'] ?? ''));
    if ($byKey !== null) {
        return $byKey;
    }
    return localize_label($label, $field['field_key'] ?? '');
}

function price_usd(float $amount): string
{
    return Currency::formatUsd($amount);
}

function cover_src(?array $good): string
{
    if (!empty($good['cover_path'])) {
        return $good['cover_path'];
    }
    if (!empty($good['cover_url'])) {
        return $good['cover_url'];
    }
    return '/assets/placeholder.png';
}

/**
 * Relative URL for CSS/JS/images — works on any host/port (unlike APP_URL-based absolute URLs).
 */
function asset(string $path): string
{
    return '/' . ltrim($path, '/');
}

function lang_url(string $path, string $lang): string
{
    $sep = str_contains($path, '?') ? '&' : '?';
    return $path . $sep . 'lang=' . $lang;
}

function t(string $key, array $replace = []): string
{
    return \App\I18n::t($key, $replace);
}

function trans_entity(string $type, string $entityId, string $field, string $ruFallback): string
{
    return \App\I18n::transEntity($type, $entityId, $field, $ruFallback);
}

function currency_label(array $good): string
{
    $ru = trim((string) ($good['currency_name_ru'] ?? ''));
    if ($ru === '') {
        return '';
    }
    $label = trans_entity('good', (string) $good['id'], 'currency', $ru);

    return \App\ListingTranslator::toEnglish(
        localize_label($label, (string) ($good['id'] ?? '')),
    );
}

function utf8_strtolower(string $text): string
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($text, 'UTF-8');
    }

    return strtolower($text);
}

function utf8_str_contains(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }
    if (function_exists('mb_strpos')) {
        return mb_strpos($haystack, $needle, 0, 'UTF-8') !== false;
    }

    return str_contains($haystack, $needle);
}

/** EN label from good_fields.field_key when available. */
function label_for_field_key(string $fieldKey): ?string
{
    if (\App\I18n::lang() === 'ru' || $fieldKey === '') {
        return null;
    }
    $map = [
        'login' => 'label_login',
        'password' => 'label_password',
        'email' => 'label_email',
        'uid' => 'label_uid',
        'player_id' => 'label_player_id',
        'server' => 'label_server',
        'nickname' => 'label_nickname',
    ];
    $key = utf8_strtolower(trim($fieldKey));
    if (!isset($map[$key])) {
        return null;
    }

    return t($map[$key]);
}

/** Translate common field/pack labels (login, password, etc.) on EN. */
function localize_label(string $text, string $contextKey = ''): string
{
    if (\App\I18n::lang() === 'ru') {
        return $text;
    }
    $byKey = label_for_field_key($contextKey);
    if ($byKey !== null) {
        return $byKey;
    }
    $trimmed = trim($text);
    $lower = utf8_strtolower($trimmed);
    $map = [
        'login' => 'label_login',
        'password' => 'label_password',
        'пароль' => 'label_password',
        'логин' => 'label_login',
        'email' => 'label_email',
        'e-mail' => 'label_email',
        'uid' => 'label_uid',
        'id' => 'label_player_id',
        'player id' => 'label_player_id',
        'server' => 'label_server',
        'nickname' => 'label_nickname',
        'ник' => 'label_nickname',
        'никнейм' => 'label_nickname',
    ];
    if (isset($map[$lower])) {
        return t($map[$lower]);
    }
    if (utf8_str_contains($trimmed, 'логин') && utf8_str_contains($trimmed, 'пароль')) {
        return t('label_login_password');
    }
    if (utf8_str_contains($trimmed, 'пароль') || utf8_str_contains($lower, 'password')) {
        return t('label_password');
    }
    if (utf8_str_contains($trimmed, 'логин') || utf8_str_contains($lower, 'login')) {
        return t('label_login');
    }

    return $text;
}


function product_seo_description(array $good, float $minPriceUsd = 0): string
{
    return \App\ProductSeoCopy::description($good, $minPriceUsd);
}

function product_seo_excerpt(array $good, float $minPriceUsd = 0): string
{
    return \App\ProductSeoCopy::excerpt($good, $minPriceUsd);
}
