<?php
require_once __DIR__ . '/includes/layout.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

$product = [
    'name' => '', 'category_id' => '', 'description' => '', 'price' => '', 'sale_price' => '',
    'stock' => 10, 'vintage' => '', 'region' => '', 'grape' => '', 'alcohol' => '',
    'image' => 'assets/img/wine-01.svg', 'featured' => 0, 'active' => 1,
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash('That product no longer exists.', 'error');
        redirect('admin/products.php');
    }
    $product = $found;
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $product['name']        = trim((string)($_POST['name'] ?? ''));
    $product['category_id'] = (int)($_POST['category_id'] ?? 0);
    $product['description'] = trim((string)($_POST['description'] ?? ''));
    $product['price']       = (float)($_POST['price'] ?? 0);
    $product['sale_price']  = ($_POST['sale_price'] ?? '') !== '' ? (float)$_POST['sale_price'] : null;
    $product['stock']       = max(0, (int)($_POST['stock'] ?? 0));
    $product['vintage']     = ($_POST['vintage'] ?? '') !== '' ? (int)$_POST['vintage'] : null;
    $product['region']      = trim((string)($_POST['region'] ?? ''));
    $product['grape']       = trim((string)($_POST['grape'] ?? ''));
    $product['alcohol']     = ($_POST['alcohol'] ?? '') !== '' ? (float)$_POST['alcohol'] : null;
    $product['featured']    = isset($_POST['featured']) ? 1 : 0;
    $product['active']      = isset($_POST['active']) ? 1 : 0;
    $product['image']       = trim((string)($_POST['image_path'] ?? $product['image']));

    if ($product['name'] === '') $errors['name'] = 'A product name is required.';
    if (!$product['category_id']) $errors['category_id'] = 'Please choose a category.';
    if ((float)$product['price'] <= 0) $errors['price'] = 'Enter a price greater than zero.';
    if ($product['sale_price'] !== null && $product['sale_price'] >= (float)$product['price']) {
        $errors['sale_price'] = 'Sale price must be lower than the regular price.';
    }

    /* ---- Optional image upload ---- */
    if (!empty($_FILES['image_file']['name']) && is_uploaded_file($_FILES['image_file']['tmp_name'])) {
        $file = $_FILES['image_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        if (!in_array($ext, $allowed, true)) {
            $errors['image_file'] = 'Allowed formats: JPG, PNG, WEBP, GIF, SVG.';
        } elseif ($file['size'] > 3 * 1024 * 1024) {
            $errors['image_file'] = 'Image must be smaller than 3 MB.';
        } else {
            $uploadDir = APP_ROOT . '/assets/img/uploads';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $filename = 'wine-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                $product['image'] = 'assets/img/uploads/' . $filename;
            } else {
                $errors['image_file'] = 'Upload failed — check folder permissions.';
            }
        }
    }

    if (!$errors) {
        $slug = slugify($product['name']);
        // Ensure unique slug
        $chk = $pdo->prepare('SELECT id FROM products WHERE slug = ? AND id <> ?');
        $chk->execute([$slug, $id]);
        if ($chk->fetch()) {
            $slug .= '-' . strtolower(bin2hex(random_bytes(2)));
        }

        $fields = [
            'category_id' => $product['category_id'],
            'name'        => $product['name'],
            'slug'        => $slug,
            'description' => $product['description'],
            'price'       => (float)$product['price'],
            'sale_price'  => $product['sale_price'],
            'stock'       => $product['stock'],
            'vintage'     => $product['vintage'],
            'region'      => $product['region'] ?: null,
            'grape'       => $product['grape'] ?: null,
            'alcohol'     => $product['alcohol'],
            'image'       => $product['image'] ?: null,
            'featured'    => $product['featured'],
            'active'      => $product['active'],
        ];

        if ($isEdit) {
            $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
            $fields['id'] = $id;
            $pdo->prepare("UPDATE products SET $set WHERE id = :id")->execute($fields);
            flash('Product updated successfully.');
        } else {
            $cols = implode(', ', array_keys($fields));
            $ph   = ':' . implode(', :', array_keys($fields));
            $pdo->prepare("INSERT INTO products ($cols) VALUES ($ph)")->execute($fields);
            flash('Product created successfully.');
        }
        redirect('admin/products.php');
    }
}

admin_header($isEdit ? 'Edit product' : 'Add product', 'products');
?>

<form class="product-form" method="post" action="<?= url('admin/product-form.php' . ($isEdit ? '?id=' . $id : '')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="form-main">
        <section class="panel">
            <header class="panel__head"><h2>Basic details</h2></header>
            <div class="panel__body">
                <label class="field">
                    <span>Product name *</span>
                    <input type="text" name="name" value="<?= e((string)$product['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?><em class="field-error"><?= e($errors['name']) ?></em><?php endif; ?>
                </label>
                <label class="field">
                    <span>Description</span>
                    <textarea name="description" rows="5" placeholder="Tasting notes, pairing suggestions…"><?= e((string)$product['description']) ?></textarea>
                </label>
                <div class="grid-3">
                    <label class="field">
                        <span>Vintage</span>
                        <input type="number" name="vintage" value="<?= e((string)($product['vintage'] ?? '')) ?>" min="1900" max="2100" placeholder="2021">
                    </label>
                    <label class="field">
                        <span>Region</span>
                        <input type="text" name="region" value="<?= e((string)$product['region']) ?>" placeholder="Bordeaux, France">
                    </label>
                    <label class="field">
                        <span>Grape</span>
                        <input type="text" name="grape" value="<?= e((string)$product['grape']) ?>" placeholder="Cabernet Sauvignon">
                    </label>
                </div>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head"><h2>Pricing &amp; inventory</h2></header>
            <div class="panel__body">
                <div class="grid-3">
                    <label class="field">
                        <span>Price (<?= e(currency()) ?>) *</span>
                        <input type="number" name="price" step="0.01" min="0" value="<?= e((string)$product['price']) ?>" required>
                        <?php if (isset($errors['price'])): ?><em class="field-error"><?= e($errors['price']) ?></em><?php endif; ?>
                    </label>
                    <label class="field">
                        <span>Sale price (<?= e(currency()) ?>)</span>
                        <input type="number" name="sale_price" step="0.01" min="0" value="<?= e((string)($product['sale_price'] ?? '')) ?>" placeholder="Optional">
                        <?php if (isset($errors['sale_price'])): ?><em class="field-error"><?= e($errors['sale_price']) ?></em><?php endif; ?>
                    </label>
                    <label class="field">
                        <span>Stock</span>
                        <input type="number" name="stock" min="0" value="<?= e((string)$product['stock']) ?>">
                    </label>
                </div>
                <label class="field field--narrow">
                    <span>Alcohol (% ABV)</span>
                    <input type="number" name="alcohol" step="0.1" min="0" max="100" value="<?= e((string)($product['alcohol'] ?? '')) ?>" placeholder="13.5">
                </label>
            </div>
        </section>
    </div>

    <aside class="form-side">
        <section class="panel">
            <header class="panel__head"><h2>Organisation</h2></header>
            <div class="panel__body">
                <label class="field">
                    <span>Category *</span>
                    <select name="category_id" required>
                        <option value="">Select…</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (int)$product['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category_id'])): ?><em class="field-error"><?= e($errors['category_id']) ?></em><?php endif; ?>
                </label>
                <label class="switch">
                    <input type="checkbox" name="featured" value="1" <?= $product['featured'] ? 'checked' : '' ?>>
                    <span class="switch__track"></span>
                    <span class="switch__label">Feature on homepage</span>
                </label>
                <label class="switch">
                    <input type="checkbox" name="active" value="1" <?= $product['active'] ? 'checked' : '' ?>>
                    <span class="switch__track"></span>
                    <span class="switch__label">Visible in store</span>
                </label>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head"><h2>Image</h2></header>
            <div class="panel__body">
                <div class="image-preview">
                    <img id="imagePreview" src="<?= e(product_image((string)$product['image'])) ?>" alt="Preview">
                </div>
                <label class="field">
                    <span>Upload image</span>
                    <input type="file" name="image_file" accept="image/*" data-image-input>
                    <small class="muted">JPG, PNG, WEBP, GIF or SVG · max 3 MB</small>
                    <?php if (isset($errors['image_file'])): ?><em class="field-error"><?= e($errors['image_file']) ?></em><?php endif; ?>
                </label>
                <label class="field">
                    <span>…or use an existing path</span>
                    <input type="text" name="image_path" value="<?= e((string)$product['image']) ?>" data-image-path>
                </label>
            </div>
        </section>

        <div class="form-actions">
            <button class="btn btn--primary btn--lg btn--block" type="submit"><?= $isEdit ? 'Save changes' : 'Create product' ?></button>
            <a class="btn btn--ghost btn--block" href="<?= url('admin/products.php') ?>">Cancel</a>
        </div>
    </aside>
</form>

<?php admin_footer(); ?>
