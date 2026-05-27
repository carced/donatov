<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Currency
{
    public static function fetchCbrUsdRate(): array
    {
        $xml = @file_get_contents('https://www.cbr.ru/scripts/XML_daily.asp');
        if ($xml === false) {
            throw new \RuntimeException('Failed to fetch CBR rates');
        }
        $doc = new \SimpleXMLElement($xml);
        foreach ($doc->Valute as $valute) {
            if ((string) $valute->CharCode === 'USD') {
                $nominal = (float) $valute->Nominal;
                $value = (float) str_replace(',', '.', (string) $valute->Value);
                $rate = $value / $nominal;
                return [
                    'date' => (string) $doc['Date'],
                    'usd_rub' => $rate,
                ];
            }
        }
        throw new \RuntimeException('USD not found in CBR feed');
    }

    public static function storeRate(PDO $pdo, array $rate): void
    {
        $date = date('Y-m-d', strtotime($rate['date']));
        $stmt = $pdo->prepare(
            'INSERT INTO fx_rates (rate_date, usd_rub, source) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE usd_rub = VALUES(usd_rub), fetched_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$date, $rate['usd_rub'], 'cbr']);
    }

    public static function latestUsdRub(PDO $pdo): float
    {
        $row = $pdo->query('SELECT usd_rub, rate_date FROM fx_rates ORDER BY rate_date DESC LIMIT 1')->fetch();
        if ($row) {
            return (float) $row['usd_rub'];
        }
        $rate = self::fetchCbrUsdRate();
        self::storeRate($pdo, $rate);
        return (float) $rate['usd_rub'];
    }

    public static function rubToUsd(float $rub, float $usdRub, float $discountFactor): float
    {
        if ($usdRub <= 0) {
            return 0.0;
        }
        return round(($rub / $usdRub) * $discountFactor, 2);
    }

    public static function discountFactor(PDO $pdo): float
    {
        $env = Config::get('DISCOUNT_FACTOR');
        if ($env !== null && $env !== '') {
            return (float) $env;
        }
        $row = $pdo->query("SELECT value FROM settings WHERE `key` = 'discount_factor'")->fetch();
        return $row ? (float) $row['value'] : 0.6;
    }

    public static function formatUsd(float $amount): string
    {
        return '$' . number_format($amount, 2, '.', ',');
    }
}
