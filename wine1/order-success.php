<?php
require_once __DIR__ . '/config/config.php';

$orderId = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

$isOwner  = $order && (int)($_SESSION['last_order'] ?? 0) === $orderId;
$isAccount = $order && is_logged_in() && (int)$order['user_id'] === (int)current_user()['id'];
if (!$order || (!$isOwner && !$isAccount && !is_admin())) {
    http_response_code(404);
    redirect('404.php');
}

$itemStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll();

$pageTitle  = 'Order confirmed';
$activePage = 'cart';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="order-success">
        <div class="order-success__icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg>
        </div>
        <h1>Thank you, <?= e(explode(' ', $order['full_name'])[0]) ?>!</h1>
        <p>Your order <strong>#<?= (int)$order['id'] ?></strong> has been placed. We've emailed a confirmation to <?= e($order['email']) ?>.</p>

        <div class="order-success__grid">
            <div class="card">
                <h3>Order summary</h3>
                <ul class="mini-cart">
                    <?php foreach ($orderItems as $item): ?>
                        <li>
                            <div><strong><?= e($item['product_name']) ?></strong><small>Qty <?= (int)$item['qty'] ?></small></div>
                            <span><?= e(money((float)$item['price'] * (int)$item['qty'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <dl class="summary-list">
                    <div><dt>Subtotal</dt><dd><?= e(money((float)$order['subtotal'])) ?></dd></div>
                    <div><dt>Delivery</dt><dd><?= (float)$order['shipping'] > 0 ? e(money((float)$order['shipping'])) : 'Free' ?></dd></div>
                    <div class="summary-list__total"><dt>Total</dt><dd><?= e(money((float)$order['total'])) ?></dd></div>
                </dl>
            </div>
            <div class="card">
                <h3>Delivery to</h3>
                <p><?= e($order['full_name']) ?><br><?= e($order['address']) ?><br><?= e($order['city']) ?> <?= e($order['postal_code'] ?? '') ?><br><?= e($order['country']) ?></p>
                <p class="muted">Payment: <?= e(strtoupper($order['payment_method'])) ?> · Status: <?= status_badge($order['status']) ?></p>
                <p class="muted small">Placed <?= e(date('M j, Y \a\t g:ia', strtotime($order['created_at']))) ?></p>
            </div>
        </div>

        <div class="order-success__actions">
            <a class="btn btn--primary" href="<?= url('shop.php') ?>">Continue shopping</a>
            <?php if (is_logged_in()): ?><a class="btn btn--ghost" href="<?= url('my-orders.php') ?>">View my orders</a><?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
