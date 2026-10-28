<?php
require_once __DIR__ . '/config/config.php';

$categories = db()->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.active = 1) AS product_count FROM categories c ORDER BY c.name')->fetchAll();
$catBySlug  = array_column($categories, null, 'slug');

$q        = trim((string)($_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$sort     = (string)($_GET['sort'] ?? 'featured');
$saleOnly = isset($_GET['sale']);
$min      = isset($_GET['min']) && $_GET['min'] !== '' ? (float)$_GET['min'] : null;
$max      = isset($_GET['max']) && $_GET['max'] !== '' ? (float)$_GET['max'] : null;
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 9;

$where  = ['p.active = 1'];
$params = [];

if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.grape LIKE ? OR p.region LIKE ? OR c.name LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($category !== '' && isset($catBySlug[$category])) {
    $where[] = 'c.slug = ?';
    $params[] = $category;
}
if ($saleOnly) {
    $where[] = 'p.sale_price IS NOT NULL AND p.sale_price > 0';
}
if ($min !== null) { $where[] = 'COALESCE(NULLIF(p.sale_price,0), p.price) >= ?'; $params[] = $min; }
if ($max !== null) { $where[] = 'COALESCE(NULLIF(p.sale_price,0), p.price) <= ?'; $params[] = $max; }

$orderMap = [
    'featured'   => 'p.featured DESC, p.id DESC',
    'newest'     => 'p.created_at DESC, p.id DESC',
    'price_asc'  => 'COALESCE(NULLIF(p.sale_price,0), p.price) ASC',
    'price_desc' => 'COALESCE(NULLIF(p.sale_price,0), p.price) DESC',
    'name'       => 'p.name ASC',
];
$order = $orderMap[$sort] ?? $orderMap['featured'];

$sql  = 'FROM products p JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where);
$stmt = db()->prepare("SELECT COUNT(*) $sql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);

$stmt = db()->prepare("SELECT p.* $sql ORDER BY $order LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$stmt->execute($params);
$products = $stmt->fetchAll();

$activeCategory = $category !== '' && isset($catBySlug[$category]) ? $catBySlug[$category] : null;

/** Build a query string preserving current filters. */
function shop_query(array $overrides = []): string
{
    $base = array_filter([
        'q'        => $_GET['q'] ?? '',
        'category' => $_GET['category'] ?? '',
        'sort'     => $_GET['sort'] ?? '',
        'sale'     => isset($_GET['sale']) ? '1' : '',
        'min'      => $_GET['min'] ?? '',
        'max'      => $_GET['max'] ?? '',
    ], fn($v) => $v !== '' && $v !== null);
    $merged = array_filter(array_merge($base, $overrides), fn($v) => $v !== '' && $v !== null && $v !== false);
    return $merged ? '?' . http_build_query($merged) : '';
}

$pageTitle  = $activeCategory ? $activeCategory['name'] : ($q !== '' ? "Search: $q" : 'Shop all wines');
$activePage = 'shop';

require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a><span>/</span>
        <a href="<?= url('shop.php') ?>">Shop</a>
        <?php if ($activeCategory): ?><span>/</span><span><?= e($activeCategory['name']) ?></span><?php endif; ?>
    </nav>

    <header class="page-head">
        <div>
            <span class="eyebrow"><?= $total ?> bottles available</span>
            <h1><?= e($activeCategory ? $activeCategory['name'] : ($q !== '' ? 'Results for “' . $q . '”' : 'All wines')) ?></h1>
            <?php if ($activeCategory && $activeCategory['description']): ?><p><?= e($activeCategory['description']) ?></p><?php endif; ?>
        </div>
    </header>

    <div class="shop-layout">
        <aside class="filters" data-filters>
            <form method="get" action="<?= url('shop.php') ?>">
                <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
                <div class="filters__head">
                    <h3>Filters</h3>
                    <a href="<?= url('shop.php') ?>">Reset</a>
                </div>

                <fieldset>
                    <legend>Category</legend>
                    <label class="radio">
                        <input type="radio" name="category" value="" <?= $category === '' ? 'checked' : '' ?>>
                        <span>All categories</span>
                    </label>
                    <?php foreach ($categories as $cat): ?>
                        <label class="radio">
                            <input type="radio" name="category" value="<?= e($cat['slug']) ?>" <?= $category === $cat['slug'] ? 'checked' : '' ?>>
                            <span><?= e($cat['name']) ?> <em><?= (int)$cat['product_count'] ?></em></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset>
                    <legend>Price</legend>
                    <div class="price-range">
                        <input type="number" name="min" placeholder="Min" min="0" value="<?= $min !== null ? e((string)$min) : '' ?>">
                        <span>–</span>
                        <input type="number" name="max" placeholder="Max" min="0" value="<?= $max !== null ? e((string)$max) : '' ?>">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Offers</legend>
                    <label class="checkbox">
                        <input type="checkbox" name="sale" value="1" <?= $saleOnly ? 'checked' : '' ?>>
                        <span>On sale only</span>
                    </label>
                </fieldset>

                <input type="hidden" name="sort" value="<?= e($sort) ?>">
                <button class="btn btn--primary btn--block" type="submit">Apply filters</button>
            </form>
        </aside>

        <section class="shop-results">
            <div class="toolbar">
                <button class="btn btn--ghost filters-toggle" type="button" data-filters-toggle>Filters</button>
                <span class="toolbar__count">Showing <?= count($products) ?> of <?= $total ?></span>
                <form class="toolbar__sort" method="get" action="<?= url('shop.php') ?>">
                    <?php foreach (['q', 'category', 'min', 'max'] as $keep): ?>
                        <?php if (!empty($_GET[$keep])): ?><input type="hidden" name="<?= e($keep) ?>" value="<?= e($_GET[$keep]) ?>"><?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($saleOnly): ?><input type="hidden" name="sale" value="1"><?php endif; ?>
                    <label>Sort
                        <select name="sort" onchange="this.form.submit()">
                            <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>Featured</option>
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
                            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A–Z</option>
                        </select>
                    </label>
                </form>
            </div>

            <?php if (!$products): ?>
                <div class="empty-state">
                    <h3>No wines matched your filters</h3>
                    <p>Try widening your price range or clearing the filters.</p>
                    <a class="btn btn--primary" href="<?= url('shop.php') ?>">Clear filters</a>
                </div>
            <?php else: ?>
                <div class="product-grid product-grid--3">
                    <?php foreach ($products as $product): require __DIR__ . '/includes/product-card.php'; endforeach; ?>
                </div>

                <?php if ($pages > 1): ?>
                    <nav class="pagination">
                        <?php if ($page > 1): ?><a class="page-link" href="<?= url('shop.php') . shop_query(['page' => $page - 1]) ?>">← Prev</a><?php endif; ?>
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <a class="page-link <?= $i === $page ? 'is-active' : '' ?>" href="<?= url('shop.php') . shop_query(['page' => $i]) ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $pages): ?><a class="page-link" href="<?= url('shop.php') . shop_query(['page' => $page + 1]) ?>">Next →</a><?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
