<?php
require_once __DIR__ . '/config/config.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$stmt = db()->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.active = 1');
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Wine not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="empty-state"><h1>We couldn\'t find that wine</h1><p>It may have sold out or been renamed.</p><a class="btn btn--primary" href="' . url('shop.php') . '">Back to shop</a></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

/* ---- Submit a review ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review') {
    require_csrf();
    $user = current_user();
    if (!$user) {
        flash('Please sign in to leave a review.', 'warning');
    } else {
        $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $comment = trim((string)($_POST['comment'] ?? ''));
        if ($comment === '') {
            flash('Please write a short review.', 'error');
        } else {
            $ins = db()->prepare('INSERT INTO reviews (product_id, user_id, author, rating, comment, approved) VALUES (?,?,?,?,?,1)');
            $ins->execute([$product['id'], $user['id'], $user['name'], $rating, $comment]);
            flash('Thank you — your review is live.');
        }
    }
    redirect('product.php?slug=' . urlencode($slug));
}

$reviewStmt = db()->prepare('SELECT * FROM reviews WHERE product_id = ? AND approved = 1 ORDER BY created_at DESC');
$reviewStmt->execute([$product['id']]);
$reviews = $reviewStmt->fetchAll();
$avgRating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

$relStmt = db()->prepare('SELECT * FROM products WHERE active = 1 AND category_id = ? AND id <> ? ORDER BY RAND() LIMIT 4');
$relStmt->execute([$product['category_id'], $product['id']]);
$related = $relStmt->fetchAll();

$discount   = product_discount($product);
$outOfStock = (int)$product['stock'] <= 0;
$details = array_filter([
    'Vintage' => $product['vintage'] ?: null,
    'Region'  => $product['region'],
    'Grape'   => $product['grape'],
    'Alcohol' => $product['alcohol'] ? $product['alcohol'] . '% ABV' : null,
    'Bottle'  => '750 ml',
]);

$pageTitle  = $product['name'];
$activePage = 'shop';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a><span>/</span>
        <a href="<?= url('shop.php') ?>">Shop</a><span>/</span>
        <a href="<?= url('shop.php?category=' . urlencode($product['category_slug'])) ?>"><?= e($product['category_name']) ?></a><span>/</span>
        <span><?= e($product['name']) ?></span>
    </nav>

    <div class="product-detail">
        <div class="product-detail__media">
            <img src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['name']) ?>">
            <?php if ($discount): ?><span class="pill pill--sale pill--lg">-<?= $discount ?>% today</span><?php endif; ?>
        </div>

        <div class="product-detail__info">
            <span class="eyebrow"><?= e($product['category_name']) ?></span>
            <h1><?= e($product['name']) ?></h1>

            <div class="rating-line">
                <span class="stars"><?= star_rating($avgRating) ?></span>
                <span><?= $reviews ? number_format($avgRating, 1) . ' · ' . count($reviews) . ' review' . (count($reviews) > 1 ? 's' : '') : 'No reviews yet' ?></span>
            </div>

            <div class="price price--xl">
                <span class="price__now"><?= e(money(product_price($product))) ?></span>
                <?php if ($discount): ?>
                    <span class="price__was"><?= e(money((float)$product['price'])) ?></span>
                    <span class="price__save">Save <?= e(money((float)$product['price'] - product_price($product))) ?></span>
                <?php endif; ?>
            </div>

            <p class="product-detail__desc"><?= nl2br(e($product['description'] ?? '')) ?></p>

            <ul class="spec-list">
                <?php foreach ($details as $label => $value): ?>
                    <li><span><?= e($label) ?></span><strong><?= e((string)$value) ?></strong></li>
                <?php endforeach; ?>
            </ul>

            <?php if ($outOfStock): ?>
                <p class="stock-note stock-note--out">Currently sold out — join the waiting list by contacting us.</p>
            <?php else: ?>
                <p class="stock-note <?= (int)$product['stock'] < 6 ? 'stock-note--low' : '' ?>">
                    <?= (int)$product['stock'] < 6 ? 'Only ' . (int)$product['stock'] . ' bottles left' : 'In stock &amp; ready to ship' ?>
                </p>
                <form class="buy-form js-add-to-cart" action="<?= url('cart.php') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                    <div class="qty">
                        <button type="button" data-qty="-1" aria-label="Decrease quantity">−</button>
                        <input type="number" name="qty" value="1" min="1" max="<?= (int)$product['stock'] ?>" aria-label="Quantity">
                        <button type="button" data-qty="1" aria-label="Increase quantity">+</button>
                    </div>
                    <button class="btn btn--primary btn--lg" type="submit">Add to cart</button>
                </form>
            <?php endif; ?>

            <ul class="assurance">
                <li>Free delivery over <?= e(money((float)setting('free_shipping_above', '2499'))) ?></li>
                <li>Secure checkout</li>
                <li>Careful, insulated packaging</li>
            </ul>
        </div>
    </div>

    <section class="detail-section">
        <div class="detail-tabs">
            <h2>Reviews<?= $reviews ? ' (' . count($reviews) . ')' : '' ?></h2>
        </div>

        <div class="reviews">
            <div class="reviews__list">
                <?php if (!$reviews): ?>
                    <p class="muted">Be the first to review this wine.</p>
                <?php else: foreach ($reviews as $rev): ?>
                    <article class="review">
                        <header>
                            <strong><?= e($rev['author']) ?></strong>
                            <span class="stars"><?= star_rating((float)$rev['rating']) ?></span>
                            <time><?= e(date('M j, Y', strtotime($rev['created_at']))) ?></time>
                        </header>
                        <p><?= nl2br(e($rev['comment'])) ?></p>
                    </article>
                <?php endforeach; endif; ?>
            </div>

            <div class="reviews__form">
                <h3>Write a review</h3>
                <?php if (!is_logged_in()): ?>
                    <p class="muted">You need an account to review. <a href="<?= url('login.php') ?>">Sign in</a> or <a href="<?= url('register.php') ?>">register</a>.</p>
                <?php else: ?>
                    <form method="post" action="<?= url('product.php?slug=' . urlencode($slug)) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="review">
                        <label class="field">
                            <span>Rating</span>
                            <select name="rating">
                                <option value="5">★★★★★ Excellent</option>
                                <option value="4">★★★★☆ Good</option>
                                <option value="3">★★★☆☆ Average</option>
                                <option value="2">★★☆☆☆ Poor</option>
                                <option value="1">★☆☆☆☆ Terrible</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Your review</span>
                            <textarea name="comment" rows="4" required placeholder="What did you think of this wine?"></textarea>
                        </label>
                        <button class="btn btn--primary" type="submit">Submit review</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($related): ?>
        <section class="detail-section">
            <div class="section-head">
                <h2>You may also like</h2>
                <a class="link-arrow" href="<?= url('shop.php?category=' . urlencode($product['category_slug'])) ?>">More <?= e($product['category_name']) ?> <span>→</span></a>
            </div>
            <div class="product-grid">
                <?php foreach ($related as $product): require __DIR__ . '/includes/product-card.php'; endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
