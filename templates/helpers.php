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
    return trans_entity('pack', $pack['good_id'] . ':' . $pack['source_pack_id'], 'name', $pack['name_ru']);
}

function field_label(array $field): string
{
    return trans_entity('good_field', $field['good_id'] . ':' . $field['field_key'], 'label', $field['label_ru']);
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
