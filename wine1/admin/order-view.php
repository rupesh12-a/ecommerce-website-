<?php
require_once __DIR__ . '/includes/layout.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT o.*, u.name AS account_name, u.email AS account_email FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    flash('Order not found.', 'error');
    redirect('admin/orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $status = (string)($_POST['status'] ?? '');
    if (in_array($status, order_statuses(), true)) {
        $pdo->prepare('UPDATE orders SET status = ?, notes = ? WHERE id = ?')->execute([$status, trim((string)$_POST['notes']), $id]);
        flash('Order updated.');
    }
    redirect('admin/order-view.php?id=' . $id);
}

$itemStmt = $pdo->prepare('SELECT oi.*, p.slug, p.image FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll();

admin_header('Order #' . $id, 'orders');
?>

<div class="detail-bar">
    <a class="link-arrow" href="<?= url('admin/orders.php') ?>">← Back to orders</a>
    <div class="detail-bar__status"><?= status_badge($order['status']) ?></div>
</div>

<div class="admin-columns admin-columns--aside">
    <div class="stack">
        <section class="panel">
            <header class="panel__head"><h2>Items</h2></header>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th></th><th>Product</th><th class="ta-r">Unit price</th><th class="ta-r">Qty</th><th class="ta-r">Line total</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="ta-c"><?php if ($item['image']): ?><img class="thumb" src="<?= e(product_image($item['image'])) ?>" alt=""><?php endif; ?></td>
                            <td>
                                <?php if ($item['slug']): ?>
                                    <a href="<?= url('product.php?slug=' . urlencode($item['slug'])) ?>" target="_blank" rel="noopener"><?= e($item['product_name']) ?></a>
                                <?php else: ?>
                                    <?= e($item['product_name']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="ta-r"><?= e(money((float)$item['price'])) ?></td>
                            <td class="ta-r"><?= (int)$item['qty'] ?></td>
                            <td class="ta-r"><strong><?= e(money((float)$item['price'] * (int)$item['qty'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="order-totals">
                <div><span>Subtotal</span><strong><?= e(money((float)$order['subtotal'])) ?></strong></div>
                <div><span>Delivery</span><strong><?= (float)$order['shipping'] > 0 ? e(money((float)$order['shipping'])) : 'Free' ?></strong></div>
                <div class="order-totals__grand"><span>Total</span><strong><?= e(money((float)$order['total'])) ?></strong></div>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head"><h2>Update order</h2></header>
            <div class="panel__body">
                <form method="post" action="<?= url('admin/order-view.php?id=' . $id) ?>" class="grid-2">
                    <?= csrf_field() ?>
                    <label class="field">
                        <span>Status</span>
                        <select name="status">
                            <?php foreach (order_statuses() as $s): ?>
                                <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field field--full">
                        <span>Internal notes</span>
                        <textarea name="notes" rows="3"><?= e((string)$order['notes']) ?></textarea>
                    </label>
                    <div class="field--full"><button class="btn btn--primary" type="submit">Save changes</button></div>
                </form>
            </div>
        </section>
    </div>

    <aside class="stack">
        <section class="panel">
            <header class="panel__head"><h2>Customer</h2></header>
            <div class="panel__body">
                <p><strong><?= e($order['full_name']) ?></strong><br>
                <a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a><br>
                <?= e((string)$order['phone']) ?></p>
                <?php if ($order['account_name']): ?>
                    <p class="muted small">Registered account: <?= e($order['account_name']) ?></p>
                <?php else: ?>
                    <p class="muted small">Guest checkout</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head"><h2>Delivery</h2></header>
            <div class="panel__body">
                <p><?= e($order['address']) ?><br><?= e($order['city']) ?> <?= e((string)$order['postal_code']) ?><br><?= e($order['country']) ?></p>
                <p class="muted small">Placed <?= e(date('M j, Y \a\t g:ia', strtotime($order['created_at']))) ?></p>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head"><h2>Payment</h2></header>
            <div class="panel__body">
                <p><span class="chip"><?= e(strtoupper($order['payment_method'])) ?></span></p>
                <p class="muted small">Total charged: <?= e(money((float)$order['total'])) ?></p>
            </div>
        </section>
    </aside>
</div>

<?php admin_footer(); ?>
