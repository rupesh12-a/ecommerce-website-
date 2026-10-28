<?php
require_once __DIR__ . '/config/config.php';
require_login();

$user = current_user();
$stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

$itemStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');

$pageTitle  = 'My orders';
$activePage = '';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a><span>/</span><span>My orders</span></nav>
    <header class="page-head">
        <div>
            <span class="eyebrow">Hello, <?= e($user['name']) ?></span>
            <h1>My orders</h1>
        </div>
        <a class="btn btn--ghost" href="<?= url('shop.php') ?>">Shop again</a>
    </header>

    <?php if (!$orders): ?>
        <div class="empty-state">
            <h3>No orders yet</h3>
            <p>When you place an order it will appear here.</p>
            <a class="btn btn--primary" href="<?= url('shop.php') ?>">Start shopping</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <?php $itemStmt->execute([$order['id']]); $orderItems = $itemStmt->fetchAll(); ?>
                <article class="order-card">
                    <header class="order-card__head">
                        <div>
                            <strong>Order #<?= (int)$order['id'] ?></strong>
                            <small><?= e(date('M j, Y', strtotime($order['created_at']))) ?></small>
                        </div>
                        <?= status_badge($order['status']) ?>
                    </header>
                    <ul class="order-card__items">
                        <?php foreach ($orderItems as $item): ?>
                            <li><span><?= e($item['product_name']) ?> × <?= (int)$item['qty'] ?></span><span><?= e(money((float)$item['price'] * (int)$item['qty'])) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <footer class="order-card__foot">
                        <span>Total <strong><?= e(money((float)$order['total'])) ?></strong></span>
                        <span class="muted"><?= e(strtoupper($order['payment_method'])) ?></span>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
