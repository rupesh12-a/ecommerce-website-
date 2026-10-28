<?php
require_once __DIR__ . '/includes/layout.php';

$pdo = db();

$revenue   = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'cancelled'")->fetchColumn();
$orderCount = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$productCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$customerCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$lowStock  = $pdo->query('SELECT id, name, stock FROM products WHERE stock <= 6 AND active = 1 ORDER BY stock ASC LIMIT 6')->fetchAll();
$recent    = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 6')->fetchAll();
$topProducts = $pdo->query('SELECT p.name, p.image, SUM(oi.qty) AS sold, SUM(oi.qty * oi.price) AS revenue
                            FROM order_items oi JOIN products p ON p.id = oi.product_id
                            GROUP BY p.id ORDER BY sold DESC LIMIT 5')->fetchAll();
$statusCounts = [];
foreach ($pdo->query('SELECT status, COUNT(*) c FROM orders GROUP BY status') as $row) {
    $statusCounts[$row['status']] = (int)$row['c'];
}
$maxStatus = max(array_merge([1], array_values($statusCounts)));

admin_header('Dashboard', 'dashboard');
?>

<section class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--wine">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16l-1 9a7 7 0 0 1-14 0zM12 13v7M8 20h8"/></svg>
        </div>
        <div><small>Total revenue</small><strong><?= e(money($revenue)) ?></strong></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--ink">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h12v16l-6-3-6 3z"/></svg>
        </div>
        <div><small>Orders</small><strong><?= $orderCount ?></strong></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--green">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4zM8 7V4h8v3"/></svg>
        </div>
        <div><small>Products</small><strong><?= $productCount ?></strong></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--gold">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/></svg>
        </div>
        <div><small>Customers</small><strong><?= $customerCount ?></strong></div>
    </div>
</section>

<div class="admin-columns">
    <section class="panel">
        <header class="panel__head">
            <h2>Recent orders</h2>
            <a class="link-arrow" href="<?= url('admin/orders.php') ?>">All orders →</a>
        </header>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th class="ta-r">Total</th><th></th></tr></thead>
                <tbody>
                <?php if (!$recent): ?>
                    <tr><td colspan="5" class="muted">No orders yet.</td></tr>
                <?php else: foreach ($recent as $order): ?>
                    <tr>
                        <td><strong>#<?= (int)$order['id'] ?></strong><br><small class="muted"><?= e(date('M j, Y', strtotime($order['created_at']))) ?></small></td>
                        <td><?= e($order['full_name']) ?><br><small class="muted"><?= e($order['city']) ?></small></td>
                        <td><?= status_badge($order['status']) ?></td>
                        <td class="ta-r"><?= e(money((float)$order['total'])) ?></td>
                        <td class="ta-r"><a class="btn btn--mini" href="<?= url('admin/order-view.php?id=' . (int)$order['id']) ?>">View</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <header class="panel__head"><h2>Orders by status</h2></header>
        <ul class="bar-list">
            <?php foreach (order_statuses() as $status): $c = $statusCounts[$status] ?? 0; ?>
                <li>
                    <span class="bar-list__label"><?= e(ucfirst($status)) ?></span>
                    <span class="bar-list__track"><span class="bar-list__fill bar-list__fill--<?= e($status) ?>" style="width: <?= (int)round(($c / $maxStatus) * 100) ?>%"></span></span>
                    <b><?= $c ?></b>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<div class="admin-columns">
    <section class="panel">
        <header class="panel__head">
            <h2>Top sellers</h2>
            <a class="link-arrow" href="<?= url('admin/products.php') ?>">Manage →</a>
        </header>
        <ul class="rank-list">
            <?php if (!$topProducts): ?><li class="muted">No sales data yet.</li><?php endif; ?>
            <?php foreach ($topProducts as $i => $tp): ?>
                <li>
                    <span class="rank"><?= $i + 1 ?></span>
                    <img src="<?= e(product_image($tp['image'])) ?>" alt="">
                    <div class="rank-list__info"><strong><?= e($tp['name']) ?></strong><small><?= (int)$tp['sold'] ?> sold · <?= e(money((float)$tp['revenue'])) ?></small></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="panel">
        <header class="panel__head">
            <h2>Low stock</h2>
            <a class="link-arrow" href="<?= url('admin/products.php') ?>">Restock →</a>
        </header>
        <ul class="stock-list">
            <?php if (!$lowStock): ?><li class="muted">Every wine is well stocked. Cheers!</li><?php endif; ?>
            <?php foreach ($lowStock as $p): ?>
                <li>
                    <div><strong><?= e($p['name']) ?></strong></div>
                    <span class="stock-pill <?= (int)$p['stock'] <= 3 ? 'stock-pill--low' : '' ?>"><?= (int)$p['stock'] ?> left</span>
                    <a class="btn btn--mini" href="<?= url('admin/product-form.php?id=' . (int)$p['id']) ?>">Edit</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<?php admin_footer(); ?>
