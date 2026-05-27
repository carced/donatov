<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Router
{
    private CatalogRepository $catalog;
    private OrderService $orders;

    public function __construct(
        private PDO $pdo,
        private string $root,
    ) {
        $this->catalog = new CatalogRepository($pdo);
        $this->orders = new OrderService($pdo, $this->catalog);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';
        $query = [];
        parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);

        if (isset($query['lang']) && in_array($query['lang'], ['ru', 'en'], true)) {
            $_SESSION['lang'] = $query['lang'];
        }

        if (empty($_SESSION['lang'])) {
            $detected = $this->detectLangFromBrowser();
            $_SESSION['lang'] = $detected ?? Config::get('DEFAULT_LANG', 'ru');
        }

        $lang = $_SESSION['lang'] ?? Config::get('DEFAULT_LANG', 'ru');
        I18n::init($this->pdo, $lang);

        ReferralService::handleIncomingRef($this->pdo, $query, $path);
        ReferralService::tryRestoreFromCookie($this->pdo);

        if ($method === 'POST') {
            $this->handlePost($path);
            return;
        }

        match ($path) {
            '/' => $this->home(),
            '/catalog' => $this->catalog($query),
            '/checkout' => $this->checkout(),
            '/cart/remove' => $this->getCartRemove($query),
            '/admin' => $this->admin(),
            '/referral' => $this->referral(),
            '/referral/dashboard' => $this->referral(),
            '/sitemap.xml' => $this->sitemap(),
            '/robots.txt' => $this->robotsTxt(),
            default => $this->dynamic($path),
        };
    }


    private function detectLangFromBrowser(): ?string
    {
        $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $parts = array_filter(array_map('trim', explode(',', (string) $header)));
        if ($parts === []) {
            return null;
        }

        $best = null;
        $bestQ = -1.0;
        foreach ($parts as $part) {
            $langTag = strtolower($part);
            $q = 1.0;

            if (str_contains($part, ';')) {
                [$langTag, $params] = array_pad(explode(';', $part, 2), 2, '');
                if (preg_match('/q=([0-9.]+)/', $params, $m)) {
                    $q = (float) $m[1];
                }
            }

            $langTag = trim($langTag);
            if ($langTag === '') {
                continue;
            }

            if (str_starts_with($langTag, 'en')) {
                if ($q > $bestQ) {
                    $bestQ = $q;
                    $best = 'en';
                }
                continue;
            }

            if (str_starts_with($langTag, 'ru')) {
                if ($q > $bestQ) {
                    $bestQ = $q;
                    $best = 'ru';
                }
                continue;
            }
        }

        return $best;
    }

    private function handlePost(string $path): void
    {
        match ($path) {
            '/cart/add' => $this->postCartAdd(),
            '/cart/remove' => $this->postCartRemove(),
            '/checkout' => $this->postCheckout(),
            '/buy/confirm' => $this->postBuyConfirm(),
            '/admin/login' => $this->postAdminLogin(),
            default => $this->notFound(),
        };
    }

    private function home(): void
    {
        $goods = $this->catalog->featuredGoods(24);
        $fx = $this->catalog->latestFx();
        $refTracked = isset($_GET['ref_tracked']);
        $this->render('home', compact('goods', 'fx', 'refTracked'));
    }

    private function catalog(array $query): void
    {
        $categoryId = $query['category'] ?? null;
        $tagId = $query['tag'] ?? null;
        $categories = $this->catalog->categories();
        $goods = $this->catalog->catalogGoods($categoryId, $tagId);
        $fx = $this->catalog->latestFx();
        $this->render('catalog', compact('categories', 'goods', 'categoryId', 'tagId', 'fx'));
    }

    private function good(string $slug): void
    {
        $good = $this->catalog->goodBySlug($slug);
        if (!$good) {
            $this->notFound();
            return;
        }
        $content = $this->catalog->goodContent((int) $good['id']);
        $packs = $this->catalog->packsForGood((int) $good['id']);
        $groups = $this->catalog->packGroups((int) $good['id']);
        $fields = $this->catalog->fieldsForGood((int) $good['id']);
        $fx = $this->catalog->latestFx();
        $cryptoWallets = CryptoPayment::wallets();
        $cryptoRates = CryptoPayment::usdPrices();
        $ref_balance = ReferralService::loggedInBalance($this->pdo);
        $ref_logged = ReferralService::isLoggedIn();
        $minPriceUsd = 0.0;
        foreach ($packs as $pack) {
            $p = (float) $pack['price_usd'];
            if ($p > 0 && ($minPriceUsd === 0.0 || $p < $minPriceUsd)) {
                $minPriceUsd = $p;
            }
        }
        $relatedGoods = $this->catalog->relatedGoods(
            (int) $good['id'],
            (string) $good['category_id'],
            8,
        );
        $productName = I18n::transEntity('good', (string) $good['id'], 'name', $good['name_ru']);
        $isGoodPage = true;
        $this->render('good', compact(
            'good',
            'content',
            'packs',
            'groups',
            'fields',
            'fx',
            'cryptoWallets',
            'cryptoRates',
            'ref_balance',
            'ref_logged',
            'isGoodPage',
            'minPriceUsd',
            'relatedGoods',
            'productName',
        ));
    }

    private function referral(): void
    {
        $account = ReferralService::ensureOwnAccount($this->pdo);
        $referralUrl = ReferralService::referralUrl($account['code']);
        $recentClicks = ReferralService::recentClicks($this->pdo, (int) $account['id']);
        $earnPerClick = ReferralService::EARN_PER_CLICK;
        $this->render('referral', compact('account', 'referralUrl', 'recentClicks', 'earnPerClick'));
    }

    private function checkout(): void
    {
        $resolved = $this->orders->resolveCart();
        $paymentMethods = $this->catalog->paymentMethods();
        $fieldsByGood = [];
        foreach (array_keys($resolved['goods']) as $goodId) {
            $fieldsByGood[$goodId] = $this->catalog->fieldsForGood((int) $goodId);
        }
        $cart = $this->orders->getCart();
        $fx = $this->catalog->latestFx();
        $this->render('checkout', compact('resolved', 'paymentMethods', 'fieldsByGood', 'cart', 'fx'));
    }

    private function orderView(int $id): void
    {
        $order = $this->orders->orderById($id);
        if (!$order) {
            $this->notFound();
            return;
        }
        $this->render('order', compact('order'));
    }

    private function postCartAdd(): void
    {
        $packId = (int) ($_POST['pack_id'] ?? 0);
        $qty = max(1, (int) ($_POST['qty'] ?? 1));
        if ($packId > 0) {
            $this->orders->addPack($packId, $qty);
        }
        $redirect = $_POST['redirect'] ?? '/checkout';
        $this->redirect($redirect);
    }

    private function postCartRemove(): void
    {
        $this->removePackFromRequest($_POST['pack_id'] ?? null);
    }

    private function getCartRemove(array $query): void
    {
        $this->removePackFromRequest($query['pack_id'] ?? null);
    }

    private function removePackFromRequest(mixed $packId): void
    {
        $id = (int) $packId;
        if ($id > 0) {
            $this->orders->removePack($id);
        }
        $this->redirect('/checkout');
    }


    private function postBuyConfirm(): void
    {
        $packId = (int) ($_POST['pack_id'] ?? 0);
        $cryptoId = trim((string) ($_POST['crypto_id'] ?? ''));
        $cryptoAmount = trim((string) ($_POST['crypto_amount'] ?? ''));
        $email = trim($_POST['email'] ?? '') ?: null;
        $useReferral = !empty($_POST['use_referral_balance']) && $_POST['use_referral_balance'] !== '0';

        $fieldValues = [];
        $goodId = (int) ($_POST['good_id'] ?? 0);
        if ($goodId > 0 && is_array($_POST['fields'] ?? null)) {
            $fieldValues[$goodId] = array_map('trim', $_POST['fields']);
        }

        try {
            $orderId = $this->orders->createCryptoOrder(
                $packId,
                I18n::lang(),
                $useReferral ? null : ($cryptoId !== '' ? $cryptoId : null),
                $useReferral ? null : ($cryptoAmount !== '' ? $cryptoAmount : null),
                $email,
                $fieldValues,
                $useReferral,
            );
            $this->redirect('/order/' . $orderId);
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $slug = trim((string) ($_POST['good_slug'] ?? ''));
            $this->redirect($slug !== '' ? '/g/' . $slug . '#crypto-payment' : '/');
        }
    }

    private function postCheckout(): void
    {
        $fieldValues = [];
        foreach ($_POST['fields'] ?? [] as $goodId => $fields) {
            if (!is_array($fields)) {
                continue;
            }
            $fieldValues[(int) $goodId] = array_map('trim', $fields);
            $this->orders->setGoodFields((int) $goodId, $fieldValues[(int) $goodId]);
        }
        $cartFields = $this->orders->getCart()['fields'] ?? [];
        foreach ($cartFields as $gid => $fields) {
            if (!isset($fieldValues[(int) $gid])) {
                $fieldValues[(int) $gid] = $fields;
            }
        }

        try {
            $orderId = $this->orders->createOrder(
                I18n::lang(),
                isset($_POST['payment_method_id']) ? (int) $_POST['payment_method_id'] : null,
                trim($_POST['email'] ?? '') ?: null,
                $fieldValues,
            );
            $this->redirect('/order/' . $orderId);
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $this->redirect('/checkout');
        }
    }

    private function admin(): void
    {
        if (empty($_SESSION['admin'])) {
            $this->render('admin_login', []);
            return;
        }
        $orders = $this->orders->allOrders();
        $this->render('admin_orders', compact('orders'));
    }

    private function postAdminLogin(): void
    {
        $pass = Config::get('ADMIN_PASSWORD', 'changeme');
        if (($_POST['password'] ?? '') === $pass) {
            $_SESSION['admin'] = true;
            $this->redirect('/admin');
            return;
        }
        $_SESSION['flash_error'] = 'Invalid password';
        $this->redirect('/admin');
    }

    private function robotsTxt(): void
    {
        $base = Seo::siteUrl();
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /order/\nDisallow: /checkout\n\nSitemap: {$base}/sitemap.xml\n";
        exit;
    }

    private function sitemap(): void
    {
        $goods = $this->catalog->allGoodsForSitemap();
        header('Content-Type: application/xml; charset=utf-8');
        echo Seo::sitemapXml($goods, I18n::lang());
        exit;
    }

    private function dynamic(string $path): void
    {
        if (preg_match('#^/g/([a-z0-9\-]+)$#', $path, $m)) {
            $this->good($m[1]);
            return;
        }
        if (preg_match('#^/order/(\d+)$#', $path, $m)) {
            $this->orderView((int) $m[1]);
            return;
        }
        $this->notFound();
    }

    private function render(string $template, array $data): void
    {
        $siteName = Config::get('APP_NAME', 'GameStore');
        $lang = I18n::lang();
        $catalogRepo = $this->catalog;
        $cartResolved = $this->orders->resolveCart();
        $flashError = $_SESSION['flash_error'] ?? null;
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
        $data['isGoodPage'] = $data['isGoodPage'] ?? false;
        $seo = Seo::forTemplate($template, $data, $this->catalog);
        $footerLinks = Seo::footerLinks($this->catalog);
        extract($data);
        ob_start();
        require_once $this->root . '/templates/helpers.php';
        include $this->root . '/templates/' . $template . '.php';
        $content = ob_get_clean();
        include $this->root . '/templates/layout.php';
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        $this->render('404', []);
    }
}
