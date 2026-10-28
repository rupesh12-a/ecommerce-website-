<?php
/**
 * Shared helper functions used across the storefront, admin and API.
 */

declare(strict_types=1);

/* =========================================================== Output */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

function product_image(?string $path): string
{
    $path = $path ?: 'assets/img/wine-01.svg';
    if (preg_match('#^https?://#', $path)) {
        return $path;
    }
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function is_ajax(): bool
{
    return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string)$text, '-') ?: 'item';
}

/* =========================================================== Settings */
function setting(string $key, ?string $default = null): ?string
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            $settings = [];
        }
    }
    return $settings[$key] ?? $default;
}

function currency(): string
{
    return setting('currency_symbol', '₹');
}

function money(float|string|null $amount): string
{
    return currency() . number_format((float)$amount, 2);
}

/* =========================================================== Products */
function product_price(array $product): float
{
    $sale = $product['sale_price'] ?? null;
    return (float)(($sale !== null && (float)$sale > 0) ? $sale : $product['price']);
}

function product_discount(array $product): int
{
    $sale = $product['sale_price'] ?? null;
    if ($sale === null || (float)$sale <= 0 || (float)$product['price'] <= 0) {
        return 0;
    }
    return (int)round((1 - ((float)$sale / (float)$product['price'])) * 100);
}

function star_rating(float $rating): string
{
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= round($rating) ? '★' : '☆';
    }
    return $out;
}

function order_statuses(): array
{
    return ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
}

function status_badge(string $status): string
{
    $class = in_array($status, order_statuses(), true) ? $status : 'pending';
    return '<span class="badge badge--' . $class . '">' . e(ucfirst($status)) . '</span>';
}

/* =========================================================== Flash */
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* =========================================================== CSRF */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!csrf_verify($token)) {
        if (is_ajax()) {
            json_response(['ok' => false, 'error' => 'Your session expired. Please refresh the page.'], 419);
        }
        http_response_code(419);
        exit('CSRF token mismatch. Please go back and try again.');
    }
}

/* =========================================================== Auth */
function current_user(): ?array
{
    static $user = null;
    if ($user !== null) {
        return $user;
    }
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    if ($user === null) {
        unset($_SESSION['user_id']);
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('index.php');
        flash('Please sign in to continue.', 'warning');
        redirect('login.php');
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('Please sign in to the admin panel.', 'warning');
        redirect('admin/login.php');
    }
}

/* =========================================================== Cart */
function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(cart());
}

function cart_add(int $productId, int $qty = 1): void
{
    $cart = cart();
    $cart[$productId] = ($cart[$productId] ?? 0) + max(1, $qty);
    $_SESSION['cart'] = $cart;
}

function cart_set(int $productId, int $qty): void
{
    $cart = cart();
    if ($qty <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $qty;
    }
    $_SESSION['cart'] = $cart;
}

function cart_remove(int $productId): void
{
    $cart = cart();
    unset($cart[$productId]);
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

/**
 * Hydrate the session cart with product rows (price, stock, image...).
 *
 * @return array<int, array<string, mixed>>
 */
function cart_items(): array
{
    $cart = cart();
    if (!$cart) {
        return [];
    }
    $ids = array_map('intval', array_keys($cart));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT id, name, slug, price, sale_price, stock, image FROM products WHERE id IN ($in)");
    $stmt->execute($ids);

    $items = [];
    foreach ($stmt->fetchAll() as $product) {
        $qty = (int)$cart[$product['id']];
        $product['qty']      = $qty;
        $product['unit']     = product_price($product);
        $product['line']     = $product['unit'] * $qty;
        $items[] = $product;
    }
    return $items;
}

/**
 * @return array{subtotal: float, shipping: float, total: float, count: int}
 */
function cart_totals(): array
{
    $subtotal = 0.0;
    $count    = 0;
    foreach (cart_items() as $item) {
        $subtotal += $item['line'];
        $count    += $item['qty'];
    }

    $shipping     = 0.0;
    $shippingFee  = (float)setting('shipping_fee', '0');
    $freeAbove    = (float)setting('free_shipping_above', '0');
    if ($subtotal > 0 && $subtotal < $freeAbove) {
        $shipping = $shippingFee;
    }

    return [
        'subtotal' => round($subtotal, 2),
        'shipping' => round($shipping, 2),
        'total'    => round($subtotal + $shipping, 2),
        'count'    => $count,
    ];
}

/* =========================================================== Orders */
function create_order(array $data, array $items, array $totals): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO orders (user_id, full_name, email, phone, address, city, postal_code, country, notes, payment_method, status, subtotal, shipping, total)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['user_id'] ?: null,
            $data['full_name'],
            $data['email'],
            $data['phone'] ?: null,
            $data['address'],
            $data['city'],
            $data['postal_code'] ?: null,
            $data['country'],
            $data['notes'] ?: null,
            $data['payment_method'],
            'pending',
            $totals['subtotal'],
            $totals['shipping'],
            $totals['total'],
        ]);
        $orderId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, qty) VALUES (?,?,?,?,?)');
        $stockStmt = $pdo->prepare('UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?');
        foreach ($items as $item) {
            $itemStmt->execute([$orderId, $item['id'], $item['name'], $item['unit'], $item['qty']]);
            $stockStmt->execute([$item['qty'], $item['id']]);
        }

        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
