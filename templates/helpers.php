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
    return trans_entity('good', (string) $good['id'], 'name', $good['name_ru']);
}

function pack_name(array $pack): string
{
    $name = trans_entity('pack', $pack['good_id'] . ':' . $pack['source_pack_id'], 'name', $pack['name_ru']);
    return localize_label($name, 'pack');
}

function field_label(array $field): string
{
    $label = trans_entity('good_field', $field['good_id'] . ':' . $field['field_key'], 'label', $field['label_ru']);
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
    return localize_label(
        trans_entity('good', (string) $good['id'], 'currency', $ru),
        (string) ($good['id'] ?? ''),
    );
}

/** Translate common field/pack labels (login, password, etc.) on EN. */
function localize_label(string $text, string $contextKey = ''): string
{
    if (\App\I18n::lang() === 'ru') {
        return $text;
    }
    $lower = mb_strtolower(trim($text));
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
    if (str_contains($lower, 'логин') && str_contains($lower, 'пароль')) {
        return t('label_login_password');
    }
    if (str_contains($lower, 'пароль')) {
        return t('label_password');
    }
    if (str_contains($lower, 'логин')) {
        return t('label_login');
    }
    return $text;
}

