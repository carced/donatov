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
        $lang = $_SESSION['lang'] ?? Config::get('DEFAULT_LANG', 'ru');
        I18n::init($this->pdo, $lang);

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
            default => $this->dynamic($path),
        };
    }

    private function handlePost(string $path): void
    {
        match ($path) {
            '/cart/add' => $this->postCartAdd(),
            '/cart/remove' => $this->postCartRemove(),
            '/checkout' => $this->postCheckout(),
            '/admin/login' => $this->postAdminLogin(),
            default => $this->notFound(),
        };
    }

    private function home(): void
    {
        $goods = $this->catalog->featuredGoods(24);
        $fx = $this->catalog->latestFx();
        $this->render('home', compact('goods', 'fx'));
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
        $this->render('good', compact('good', 'content', 'packs', 'groups', 'fields', 'fx'));
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
        extract($data);
        $siteName = Config::get('APP_NAME', 'GameStore');
        $lang = I18n::lang();
        $catalogRepo = $this->catalog;
        $cartResolved = $this->orders->resolveCart();
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
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
