<?php

declare(strict_types=1);

namespace App;

use PDO;

final class ReferralService
{
    public const EARN_PER_CLICK = 0.10;

    public static function loggedInId(): ?int
    {
        $id = $_SESSION['referral_account_id'] ?? null;
        return $id ? (int) $id : null;
    }

    public static function isLoggedIn(): bool
    {
        return self::loggedInId() !== null;
    }

    public static function loggedInBalance(PDO $pdo): float
    {
        $id = self::loggedInId();
        if ($id === null) {
            return 0.0;
        }
        return self::getBalance($pdo, $id);
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

    public static function accountByEmail(PDO $pdo, string $email): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM referral_accounts WHERE email = ?');
        $stmt->execute([strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function login(PDO $pdo, int $accountId): void
    {
        $_SESSION['referral_account_id'] = $accountId;
    }

    public static function logout(): void
    {
        unset($_SESSION['referral_account_id']);
    }

    public static function register(PDO $pdo, string $email, string $password): int
    {
        $email = strtolower(trim($email));
        if ($email === '' || strlen($password) < 6) {
            throw new \RuntimeException('Invalid email or password (min 6 chars)');
        }
        if (self::accountByEmail($pdo, $email)) {
            throw new \RuntimeException('Email already registered');
        }
        $code = self::generateUniqueCode($pdo);
        $stmt = $pdo->prepare(
            'INSERT INTO referral_accounts (code, email, password_hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$code, $email, password_hash($password, PASSWORD_DEFAULT)]);
        $id = (int) $pdo->lastInsertId();
        self::login($pdo, $id);
        return $id;
    }

    public static function authenticate(PDO $pdo, string $email, string $password): int
    {
        $account = self::accountByEmail($pdo, strtolower(trim($email)));
        if (!$account || !password_verify($password, $account['password_hash'])) {
            throw new \RuntimeException('Invalid credentials');
        }
        self::login($pdo, (int) $account['id']);
        return (int) $account['id'];
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
        if (self::loggedInId() === $accountId) {
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
