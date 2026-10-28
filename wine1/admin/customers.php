<?php
require_once __DIR__ . '/includes/layout.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if (($action = $_POST['action'] ?? '') === 'delete' && $id && $id !== (int)current_user()['id']) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        flash('Account deleted.');
    } elseif ($action === 'role' && $id) {
        $role = $_POST['role'] === 'admin' ? 'admin' : 'customer';
        if ($id === (int)current_user()['id'] && $role === 'customer') {
            flash('You cannot remove your own admin access.', 'error');
        } else {
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
            flash('Role updated.');
        }
    }
    redirect('admin/customers.php');
}

$q = trim((string)($_GET['q'] ?? ''));
$where = '1=1';
$params = [];
if ($q !== '') {
    $where = '(u.name LIKE ? OR u.email LIKE ?)';
    $like = "%$q%";
    $params = [$like, $like];
}

$stmt = $pdo->prepare(
    "SELECT u.*,
            COUNT(o.id) AS order_count,
            COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN o.total END), 0) AS spend
     FROM users u
     LEFT JOIN orders o ON o.user_id = u.id
     WHERE $where
     GROUP BY u.id
     ORDER BY spend DESC, u.created_at DESC"
);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$me = (int)current_user()['id'];

admin_header('Customers', 'customers');
?>

<section class="panel">
    <header class="panel__head">
        <h2><?= count($customers) ?> account<?= count($customers) === 1 ? '' : 's' ?></h2>
        <form class="admin-filters" method="get" action="<?= url('admin/customers.php') ?>">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or email">
            <button class="btn" type="submit">Search</button>
        </form>
    </header>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Customer</th><th>Contact</th><th>Role</th><th class="ta-r">Orders</th><th class="ta-r">Lifetime spend</th><th>Joined</th><th class="ta-r">Actions</th></tr></thead>
            <tbody>
            <?php if (!$customers): ?>
                <tr><td colspan="7" class="muted">No accounts found.</td></tr>
            <?php else: foreach ($customers as $c): ?>
                <tr>
                    <td>
                        <div class="cell-user">
                            <span class="avatar avatar--sm"><?= e(mb_substr($c['name'], 0, 1)) ?></span>
                            <strong><?= e($c['name']) ?></strong>
                        </div>
                    </td>
                    <td><?= e($c['email']) ?><?php if ($c['phone']): ?><br><small class="muted"><?= e($c['phone']) ?></small><?php endif; ?></td>
                    <td>
                        <form class="inline-form" method="post" action="<?= url('admin/customers.php') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="role">
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <select class="status-select" name="role" onchange="this.form.submit()" <?= (int)$c['id'] === $me ? 'disabled' : '' ?>>
                                <option value="customer" <?= $c['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                <option value="admin" <?= $c['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </form>
                    </td>
                    <td class="ta-r"><?= (int)$c['order_count'] ?></td>
                    <td class="ta-r"><?= e(money((float)$c['spend'])) ?></td>
                    <td><?= e(date('M j, Y', strtotime($c['created_at']))) ?></td>
                    <td class="ta-r">
                        <?php if ((int)$c['id'] !== $me): ?>
                            <form method="post" action="<?= url('admin/customers.php') ?>" data-confirm="Delete account “<?= e($c['name']) ?>”?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button class="btn btn--mini btn--danger" type="submit">Delete</button>
                            </form>
                        <?php else: ?><span class="muted small">You</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php admin_footer(); ?>
