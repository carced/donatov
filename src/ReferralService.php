<?php

declare(strict_types=1);

namespace App;

use PDO;

final class ReferralService
{
    public const EARN_PER_CLICK = 0.10;

    private const COOKIE_NAME = 'ref_owner';

    public static function currentAccountId(): ?int
    {
        $id = $_SESSION['referral_account_id'] ?? null;
        return $id ? (int) $id : null;
    }

    /** @deprecated Use currentAccountId() */
    public static function loggedInId(): ?int
    {
        return self::currentAccountId();
    }

    public static function isLoggedIn(): bool
    {
        return self::currentAccountId() !== null;
    }

    public static function balanceForSession(PDO $pdo): float
    {
        $id = self::currentAccountId();
        if ($id === null) {
            return 0.0;
        }
        return self::getBalance($pdo, $id);
    }

    /** Restore referral account from cookie without creating a new one. */
    public static function tryRestoreFromCookie(PDO $pdo): void
    {
        if (self::currentAccountId() !== null) {
            return;
        }
        $account = self::restoreFromCookie($pdo);
        if ($account !== null) {
            self::bindAccount($account);
        }
    }

    /** @deprecated Use balanceForSession() */
    public static function loggedInBalance(PDO $pdo): float
    {
        return self::balanceForSession($pdo);
    }

    /** Get or create this visitor's referral account (no sign-up). */
    public static function ensureOwnAccount(PDO $pdo): array
    {
        $fromSession = self::restoreFromSession($pdo);
        if ($fromSession !== null) {
            return $fromSession;
        }

        $fromCookie = self::restoreFromCookie($pdo);
        if ($fromCookie !== null) {
            self::bindAccount($fromCookie);
            return $fromCookie;
        }

        $account = self::createAnonymousAccount($pdo);
        self::bindAccount($account);
        return $account;
    }

    private static function restoreFromSession(PDO $pdo): ?array
    {
        $id = self::currentAccountId();
        if ($id === null) {
            return null;
        }
        $account = self::accountById($pdo, $id);
        if ($account === null) {
            unset($_SESSION['referral_account_id']);
            return null;
        }
        return $account;
    }

    private static function restoreFromCookie(PDO $pdo): ?array
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? '';
        if ($token === '') {
            return null;
        }
        $id = self::verifyAccountToken($token);
        if ($id === null) {
            return null;
        }
        return self::accountById($pdo, $id);
    }

    private static function bindAccount(array $account): void
    {
        $_SESSION['referral_account_id'] = (int) $account['id'];
        self::setOwnerCookie((int) $account['id']);
    }

    private static function createAnonymousAccount(PDO $pdo): array
    {
        $code = self::generateUniqueCode($pdo);
        $stmt = $pdo->prepare('INSERT INTO referral_accounts (code, email, password_hash) VALUES (?, NULL, NULL)');
        $stmt->execute([$code]);
        $id = (int) $pdo->lastInsertId();
        $account = self::accountById($pdo, $id);
        if ($account === null) {
            throw new \RuntimeException('Could not create referral account');
        }
        return $account;
    }

    public static function accountById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM referral_accounts WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function accountByCode(PDO $pdo, string $code): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM referral_accounts WHERE code = ?');
        $stmt->execute([strtoupper(trim($code))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function generateUniqueCode(PDO $pdo): string
    {
        for ($i = 0; $i < 20; $i++) {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            if (!self::accountByCode($pdo, $code)) {
                return $code;
            }
        }
        throw new \RuntimeException('Could not generate referral code');
    }

    public static function referralUrl(string $code): string
    {
        $base = rtrim(Config::get('APP_URL', 'http://localhost:8080'), '/');
        return $base . '/r/' . urlencode(strtoupper($code));
    }

    public static function accountToken(int $accountId): string
    {
        $secret = Config::get('APP_SECRET', Config::get('ADMIN_PASSWORD', 'changeme'));
        $sig = hash_hmac('sha256', (string) $accountId, $secret);
        return $accountId . '.' . substr($sig, 0, 32);
    }

    private static function verifyAccountToken(string $token): ?int
    {
        if (!preg_match('/^(\d+)\.([a-f0-9]{32})$/', $token, $m)) {
            return null;
        }
        $id = (int) $m[1];
        $expected = self::accountToken($id);
        if (!hash_equals($expected, $token)) {
            return null;
        }
        return $id;
    }

    private static function setOwnerCookie(int $accountId): void
    {
        setcookie(self::COOKIE_NAME, self::accountToken($accountId), [
            'expires' => time() + 86400 * 365,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[self::COOKIE_NAME] = self::accountToken($accountId);
    }

    /** Track ?ref= or /r/CODE click; credits $0.10 per unique visitor per day. */
    public static function trackClick(PDO $pdo, ?string $code): void
    {
        if ($code === null || $code === '') {
            return;
        }
        $account = self::accountByCode($pdo, $code);
        if (!$account) {
            return;
        }
        $accountId = (int) $account['id'];
        if (self::currentAccountId() === $accountId) {
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $day = date('Y-m-d');
        $visitorHash = hash('sha256', $ip . '|' . $ua . '|' . $day);

        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO referral_clicks (account_id, visitor_hash, ip_address, amount_usd, click_date)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$accountId, $visitorHash, $ip, self::EARN_PER_CLICK, $day]);
        if ($stmt->rowCount() > 0) {
            $pdo->prepare(
                'UPDATE referral_accounts
                 SET balance_usd = balance_usd + ?, total_clicks = total_clicks + 1, total_earned_usd = total_earned_usd + ?
                 WHERE id = ?'
            )->execute([self::EARN_PER_CLICK, self::EARN_PER_CLICK, $accountId]);
        }

        setcookie('ref', strtoupper($account['code']), [
            'expires' => time() + 86400 * 30,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['ref'] = strtoupper($account['code']);
    }

    public static function handleIncomingRef(PDO $pdo, array $query, string $path): void
    {
        if (preg_match('#^/r/([A-Za-z0-9]+)$#', $path, $m)) {
            self::trackClick($pdo, $m[1]);
            header('Location: /?ref_tracked=1');
            exit;
        }
        $ref = $query['ref'] ?? $_COOKIE['ref'] ?? null;
        if ($ref !== null && $ref !== '') {
            if (!isset($_SESSION['ref_tracked_' . strtoupper((string) $ref)])) {
                self::trackClick($pdo, (string) $ref);
                $_SESSION['ref_tracked_' . strtoupper((string) $ref)] = true;
            }
        }
    }

    public static function deductBalance(PDO $pdo, int $accountId, float $amount): void
    {
        $stmt = $pdo->prepare(
            'UPDATE referral_accounts SET balance_usd = balance_usd - ? WHERE id = ? AND balance_usd >= ?'
        );
        $stmt->execute([$amount, $accountId, $amount]);
        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException('Insufficient referral balance');
        }
    }

    public static function getBalance(PDO $pdo, int $accountId): float
    {
        $stmt = $pdo->prepare('SELECT balance_usd FROM referral_accounts WHERE id = ?');
        $stmt->execute([$accountId]);
        $row = $stmt->fetch();
        return $row ? (float) $row['balance_usd'] : 0.0;
    }

    public static function recentClicks(PDO $pdo, int $accountId, int $limit = 20): array
    {
        $stmt = $pdo->prepare(
            'SELECT click_date, amount_usd, created_at
             FROM referral_clicks
             WHERE account_id = ?
             ORDER BY id DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $accountId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
