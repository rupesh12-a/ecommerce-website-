<?php
require_once __DIR__ . '/includes/layout.php';

$pdo = db();
$edit = null;

/* ---- Actions ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $name   = trim((string)($_POST['name'] ?? ''));
    $desc   = trim((string)($_POST['description'] ?? ''));

    if ($action === 'save') {
        if ($name === '') {
            flash('Category name is required.', 'error');
        } else {
            $slug = slugify($name);
            $chk = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND id <> ?');
            $chk->execute([$slug, $id]);
            if ($chk->fetch()) {
                $slug .= '-' . strtolower(bin2hex(random_bytes(2)));
            }
            if ($id) {
                $pdo->prepare('UPDATE categories SET name=?, slug=?, description=? WHERE id=?')->execute([$name, $slug, $desc, $id]);
                flash('Category updated.');
            } else {
                $pdo->prepare('INSERT INTO categories (name, slug, description) VALUES (?,?,?)')->execute([$name, $slug, $desc]);
                flash('Category added.');
            }
        }
    } elseif ($action === 'delete' && $id) {
        $count = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE category_id = ' . $id)->fetchColumn();
        if ($count > 0) {
            flash("Cannot delete — $count product(s) still use this category. Move them first.", 'error');
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('Category deleted.');
        }
    }
    redirect('admin/categories.php');
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

$categories = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count FROM categories c ORDER BY c.name')->fetchAll();

admin_header('Categories', 'categories');
?>

<div class="admin-columns admin-columns--aside">
    <section class="panel">
        <header class="panel__head"><h2><?= count($categories) ?> categories</h2></header>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Slug</th><th class="ta-r">Products</th><th class="ta-r">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td>
                            <strong><?= e($cat['name']) ?></strong>
                            <?php if ($cat['description']): ?><br><small class="muted"><?= e($cat['description']) ?></small><?php endif; ?>
                        </td>
                        <td><code><?= e($cat['slug']) ?></code></td>
                        <td class="ta-r"><?= (int)$cat['product_count'] ?></td>
                        <td class="ta-r">
                            <div class="row-actions">
                                <a class="btn btn--mini" href="<?= url('admin/categories.php?edit=' . (int)$cat['id']) ?>">Edit</a>
                                <form method="post" action="<?= url('admin/categories.php') ?>" data-confirm="Delete category “<?= e($cat['name']) ?>”?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                    <button class="btn btn--mini btn--danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside class="panel panel--sticky">
        <header class="panel__head"><h2><?= $edit ? 'Edit category' : 'Add category' ?></h2></header>
        <div class="panel__body">
            <form method="post" action="<?= url('admin/categories.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
                <label class="field">
                    <span>Name *</span>
                    <input type="text" name="name" value="<?= e((string)($edit['name'] ?? '')) ?>" required>
                </label>
                <label class="field">
                    <span>Description</span>
                    <textarea name="description" rows="3" placeholder="Short blurb for the category page"><?= e((string)($edit['description'] ?? '')) ?></textarea>
                </label>
                <button class="btn btn--primary btn--block" type="submit"><?= $edit ? 'Save changes' : 'Add category' ?></button>
                <?php if ($edit): ?><a class="btn btn--ghost btn--block" href="<?= url('admin/categories.php') ?>">Cancel</a><?php endif; ?>
            </form>
        </div>
    </aside>
</div>

<?php admin_footer(); ?>
