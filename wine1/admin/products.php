<?php
require_once __DIR__ . '/includes/layout.php';

$pdo = db();

/* ---- Actions ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'delete' && $id) {
        $del = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $del->execute([$id]);
        flash('Product deleted.');
    } elseif ($action === 'toggle' && $id) {
        $pdo->prepare('UPDATE products SET active = 1 - active WHERE id = ?')->execute([$id]);
        flash('Product visibility updated.');
    } elseif ($action === 'feature' && $id) {
        $pdo->prepare('UPDATE products SET featured = 1 - featured WHERE id = ?')->execute([$id]);
        flash('Featured status updated.');
    } elseif ($action === 'stock' && $id) {
        $stock = max(0, (int)($_POST['stock'] ?? 0));
        $pdo->prepare('UPDATE products SET stock = ? WHERE id = ?')->execute([$stock, $id]);
        flash('Stock updated.');
    }
    redirect('admin/products.php' . (($_POST['return'] ?? '') ? '?' . $_POST['return'] : ''));
}

/* ---- Filters ---- */
$q        = trim((string)($_GET['q'] ?? ''));
$category = (string)($_GET['category'] ?? '');
$status   = (string)($_GET['status'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.region LIKE ? OR p.grape LIKE ?)';
    $like = "%$q%";
    array_push($params, $like, $like, $like);
}
if ($category !== '') { $where[] = 'c.slug = ?'; $params[] = $category; }
if ($status === 'low')  { $where[] = 'p.stock <= 6'; }
if ($status === 'out')  { $where[] = 'p.stock = 0'; }
if ($status === 'active')   { $where[] = 'p.active = 1'; }
if ($status === 'hidden')   { $where[] = 'p.active = 0'; }
if ($status === 'featured') { $where[] = 'p.featured = 1'; }

$sql = 'FROM products p JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT COUNT(*) $sql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name $sql ORDER BY p.id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT slug, name FROM categories ORDER BY name')->fetchAll();

$returnQs = http_build_query(array_filter(['q' => $q, 'category' => $category, 'status' => $status, 'page' => $page > 1 ? $page : '']));
$keep = fn(array $extra = []) => http_build_query(array_filter(array_merge(['q' => $q, 'category' => $category, 'status' => $status, 'page' => $page > 1 ? $page : ''], $extra)));

admin_header('Products', 'products');
?>

<div class="toolbar toolbar--admin">
    <form class="admin-filters" method="get" action="<?= url('admin/products.php') ?>">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, region or grape">
        <select name="category" onchange="this.form.submit()">
            <option value="">All categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c['slug']) ?>" <?= $category === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" onchange="this.form.submit()">
            <option value="">Any status</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Visible</option>
            <option value="hidden" <?= $status === 'hidden' ? 'selected' : '' ?>>Hidden</option>
            <option value="featured" <?= $status === 'featured' ? 'selected' : '' ?>>Featured</option>
            <option value="low" <?= $status === 'low' ? 'selected' : '' ?>>Low stock</option>
            <option value="out" <?= $status === 'out' ? 'selected' : '' ?>>Out of stock</option>
        </select>
        <button class="btn" type="submit">Filter</button>
    </form>
    <a class="btn btn--primary" href="<?= url('admin/product-form.php') ?>">+ Add product</a>
</div>

<section class="panel">
    <header class="panel__head">
        <h2><?= $total ?> product<?= $total === 1 ? '' : 's' ?></h2>
    </header>
    <div class="table-wrap">
        <table class="data-table data-table--products">
            <thead>
                <tr><th></th><th>Product</th><th>Category</th><th class="ta-r">Price</th><th class="ta-r">Stock</th><th>Status</th><th class="ta-r">Actions</th></tr>
            </thead>
            <tbody>
            <?php if (!$products): ?>
                <tr><td colspan="7" class="muted">No products match these filters.</td></tr>
            <?php else: foreach ($products as $p): ?>
                <tr class="<?= (int)$p['active'] ? '' : 'row--muted' ?>">
                    <td class="ta-c"><img class="thumb" src="<?= e(product_image($p['image'])) ?>" alt=""></td>
                    <td>
                        <strong><?= e($p['name']) ?></strong>
                        <br><small class="muted"><?= e($p['region'] ?? '') ?><?= !empty($p['vintage']) ? ' · ' . e((string)$p['vintage']) : '' ?></small>
                    </td>
                    <td><?= e($p['category_name']) ?></td>
                    <td class="ta-r">
                        <?php if (product_discount($p)): ?>
                            <strong><?= e(money(product_price($p))) ?></strong><br><small class="muted strike"><?= e(money((float)$p['price'])) ?></small>
                        <?php else: ?>
                            <?= e(money((float)$p['price'])) ?>
                        <?php endif; ?>
                    </td>
                    <td class="ta-r">
                        <form class="inline-form" method="post" action="<?= url('admin/products.php') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="stock">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <input type="hidden" name="return" value="<?= e($returnQs) ?>">
                            <input class="stock-input" type="number" name="stock" value="<?= (int)$p['stock'] ?>" min="0" onchange="this.form.submit()">
                        </form>
                    </td>
                    <td>
                        <?php if (!$p['active']): ?><span class="badge badge--muted">Hidden</span>
                        <?php elseif ((int)$p['stock'] === 0): ?><span class="badge badge--cancelled">Out of stock</span>
                        <?php elseif ((int)$p['stock'] <= 6): ?><span class="badge badge--pending">Low stock</span>
                        <?php else: ?><span class="badge badge--delivered">Live</span><?php endif; ?>
                    </td>
                    <td class="ta-r">
                        <div class="row-actions">
                            <form method="post" action="<?= url('admin/products.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="feature"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="return" value="<?= e($returnQs) ?>">
                                <button class="icon-action <?= $p['featured'] ? 'is-on' : '' ?>" title="Toggle featured">★</button>
                            </form>
                            <form method="post" action="<?= url('admin/products.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="return" value="<?= e($returnQs) ?>">
                                <button class="icon-action" title="<?= $p['active'] ? 'Hide' : 'Show' ?>"><?= $p['active'] ? '👁' : '🚫' ?></button>
                            </form>
                            <a class="btn btn--mini" href="<?= url('admin/product-form.php?id=' . (int)$p['id']) ?>">Edit</a>
                            <form method="post" action="<?= url('admin/products.php') ?>" data-confirm="Delete “<?= e($p['name']) ?>”? This cannot be undone.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="return" value="<?= e($returnQs) ?>">
                                <button class="btn btn--mini btn--danger" type="submit">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a class="page-link <?= $i === $page ? 'is-active' : '' ?>" href="<?= url('admin/products.php') . '?' . $keep(['page' => $i]) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>

<?php admin_footer(); ?>
