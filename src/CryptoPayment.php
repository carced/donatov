<?php

declare(strict_types=1);

namespace App;

final class CryptoPayment
{
    /** @return list<array{id:string,symbol:string,name:string,network:string,icon:string,address:string,coingecko_id:string,decimals:int}> */
    public static function wallets(): array
    {
        return [
            [
                'id' => 'btc',
                'symbol' => 'BTC',
                'name' => 'Bitcoin',
                'network' => 'Bitcoin Network',
                'icon' => '₿',
                'address' => 'bc1qqn928zzj6mwa2gy5yc92ds3lg02q4cvl8wr8sj',
                'coingecko_id' => 'bitcoin',
                'decimals' => 8,
            ],
            [
                'id' => 'eth',
                'symbol' => 'ETH',
                'name' => 'Ethereum',
                'network' => 'ERC-20',
                'icon' => 'Ξ',
                'address' => '0x4B45e9FA3C5CFB3F659d5B32EDB8622D8E230E3c',
                'coingecko_id' => 'ethereum',
                'decimals' => 6,
            ],
            [
                'id' => 'usdt_trc20',
                'symbol' => 'USDT',
                'name' => 'Tether (USDT)',
                'network' => 'TRC-20 (Tron)',
                'icon' => '₮',
                'address' => '0x4B45e9FA3C5CFB3F659d5B32EDB8622D8E230E3c',
                'coingecko_id' => 'tether',
                'decimals' => 2,
            ],
            [
                'id' => 'usdc',
                'symbol' => 'USDC',
                'name' => 'USDC',
                'network' => 'ERC-20',
                'icon' => '$',
                'address' => '0x4B45e9FA3C5CFB3F659d5B32EDB8622D8E230E3c',
                'coingecko_id' => 'usd-coin',
                'decimals' => 2,
            ],
            [
                'id' => 'ltc',
                'symbol' => 'LTC',
                'name' => 'Litecoin',
                'network' => 'Litecoin Network',
                'icon' => 'Ł',
                'address' => 'ltc1qmqlptxr9k0yyke9gc5r7k5h42ldc9pp8eqjnzh',
                'coingecko_id' => 'litecoin',
                'decimals' => 6,
            ],
            [
                'id' => 'xrp',
                'symbol' => 'XRP',
                'name' => 'XRP',
                'network' => 'XRP Ledger',
                'icon' => '✕',
                'address' => 'rLYDRjW5kDQakfVTARfGqoMrGsxkMYLUwH',
                'coingecko_id' => 'ripple',
                'decimals' => 4,
            ],
            [
                'id' => 'trx',
                'symbol' => 'TRX',
                'name' => 'TRON (TRX)',
                'network' => 'Tron Network',
                'icon' => '◈',
                'address' => 'TLJ7KNwiNppoZp43rzdNNojmVToeAQ7wix',
                'coingecko_id' => 'tron',
                'decimals' => 2,
            ],
            [
                'id' => 'bnb',
                'symbol' => 'BNB',
                'name' => 'BNB',
                'network' => 'BNB Smart Chain (BEP-20)',
                'icon' => '◆',
                'address' => '0x4B45e9FA3C5CFB3F659d5B32EDB8622D8E230E3c',
                'coingecko_id' => 'binancecoin',
                'decimals' => 6,
            ],
        ];
    }

    public static function walletById(string $id): ?array
    {
        foreach (self::wallets() as $w) {
            if ($w['id'] === $id) {
                return $w;
            }
        }
        return null;
    }

    /** @return array<string, float> coingecko_id => usd price */
    public static function usdPrices(): array
    {
        $cacheFile = dirname(__DIR__) . '/data/crypto_rates_cache.json';
        if (is_file($cacheFile)) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached) && ($cached['expires'] ?? 0) > time() && is_array($cached['prices'] ?? null)) {
                return $cached['prices'];
            }
        }

        $ids = array_unique(array_column(self::wallets(), 'coingecko_id'));
        $url = 'https://api.coingecko.com/api/v3/simple/price?ids='
            . implode(',', $ids)
            . '&vs_currencies=usd';

        $prices = self::fallbackUsdPrices();
        $resp = @file_get_contents($url);
        if ($resp !== false) {
            $data = json_decode($resp, true);
            if (is_array($data)) {
                foreach (self::wallets() as $w) {
                    $cg = $w['coingecko_id'];
                    if (isset($data[$cg]['usd'])) {
                        $prices[$cg] = (float) $data[$cg]['usd'];
                    }
                }
            }
        }

        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents($cacheFile, json_encode([
            'expires' => time() + 300,
            'prices' => $prices,
        ]));

        return $prices;
    }

    /** @return array<string, float> */
    private static function fallbackUsdPrices(): array
    {
        return [
            'bitcoin' => 95000.0,
            'ethereum' => 3500.0,
            'tether' => 1.0,
            'usd-coin' => 1.0,
            'litecoin' => 90.0,
            'ripple' => 0.55,
            'tron' => 0.12,
            'binancecoin' => 600.0,
        ];
    }

    public static function usdToCryptoAmount(float $usd, array $wallet, array $usdPrices): string
    {
        $cg = $wallet['coingecko_id'];
        $price = $usdPrices[$cg] ?? 0.0;
        if ($price <= 0) {
            return '0';
        }
        $amount = $usd / $price;
        $decimals = (int) $wallet['decimals'];
        return rtrim(rtrim(number_format($amount, $decimals, '.', ''), '0'), '.') ?: '0';
    }
}
