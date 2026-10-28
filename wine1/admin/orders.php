<?php
require_once __DIR__ . '/includes/layout.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');

    if ($action === 'status' && $id && in_array($status, order_statuses(), true)) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
        flash("Order #$id marked as " . ucfirst($status) . '.');
    }
    redirect('admin/orders.php' . (($_POST['return'] ?? '') ? '?' . $_POST['return'] : ''));
}

$status = (string)($_GET['status'] ?? '');
$q      = trim((string)($_GET['q'] ?? ''));
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$where = ['1=1'];
$params = [];
if (in_array($status, order_statuses(), true)) { $where[] = 'o.status = ?'; $params[] = $status; }
if ($q !== '') {
    $where[] = '(o.full_name LIKE ? OR o.email LIKE ? OR o.id = ?)';
    $like = "%$q%";
    array_push($params, $like, $like, (int)$q);
}

$sql = 'FROM orders o WHERE ' . implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT COUNT(*) $sql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare("SELECT o.*, (SELECT SUM(qty) FROM order_items oi WHERE oi.order_id = o.id) AS item_count $sql ORDER BY o.created_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$stmt->execute($params);
$orders = $stmt->fetchAll();

$counts = [];
foreach ($pdo->query('SELECT status, COUNT(*) c FROM orders GROUP BY status') as $row) {
    $counts[$row['status']] = (int)$row['c'];
}
$totalOrders = array_sum($counts);

$returnQs = http_build_query(array_filter(['status' => $status, 'q' => $q, 'page' => $page > 1 ? $page : '']));
$keep = fn(array $extra = []) => http_build_query(array_filter(array_merge(['status' => $status, 'q' => $q, 'page' => $page > 1 ? $page : ''], $extra)));

admin_header('Orders', 'orders');
?>

<div class="tabs">
    <a class="tab <?= $status === '' ? 'is-active' : '' ?>" href="<?= url('admin/orders.php') ?>">All <span><?= $totalOrders ?></span></a>
    <?php foreach (order_statuses() as $s): ?>
        <a class="tab <?= $status === $s ? 'is-active' : '' ?>" href="<?= url('admin/orders.php?status=' . $s) ?>"><?= e(ucfirst($s)) ?> <span><?= $counts[$s] ?? 0 ?></span></a>
    <?php endforeach; ?>
</div>

<section class="panel">
    <header class="panel__head">
        <h2><?= $total ?> order<?= $total === 1 ? '' : 's' ?></h2>
        <form class="admin-filters" method="get" action="<?= url('admin/orders.php') ?>">
            <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email or #id">
            <button class="btn" type="submit">Search</button>
        </form>
    </header>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Payment</th><th>Status</th><th class="ta-r">Total</th><th class="ta-r">Actions</th></tr></thead>
            <tbody>
            <?php if (!$orders): ?>
                <tr><td colspan="7" class="muted">No orders found.</td></tr>
            <?php else: foreach ($orders as $order): ?>
                <tr>
                    <td><strong>#<?= (int)$order['id'] ?></strong><br><small class="muted"><?= e(date('M j, Y', strtotime($order['created_at']))) ?></small></td>
                    <td><?= e($order['full_name']) ?><br><small class="muted"><?= e($order['email']) ?></small></td>
                    <td><?= (int)$order['item_count'] ?></td>
                    <td><span class="chip"><?= e(strtoupper($order['payment_method'])) ?></span></td>
                    <td>
                        <form class="inline-form" method="post" action="<?= url('admin/orders.php') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                            <input type="hidden" name="return" value="<?= e($returnQs) ?>">
                            <select class="status-select" name="status" onchange="this.form.submit()">
                                <?php foreach (order_statuses() as $s): ?>
                                    <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="ta-r"><strong><?= e(money((float)$order['total'])) ?></strong></td>
                    <td class="ta-r"><a class="btn btn--mini" href="<?= url('admin/order-view.php?id=' . (int)$order['id']) ?>">View</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <nav class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a class="page-link <?= $i === $page ? 'is-active' : '' ?>" href="<?= url('admin/orders.php') . '?' . $keep(['page' => $i]) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>

<?php admin_footer(); ?>
