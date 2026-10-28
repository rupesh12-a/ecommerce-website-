<?php
require_once __DIR__ . '/config/config.php';

$categories = db()->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.active = 1) AS product_count FROM categories c ORDER BY c.id')->fetchAll();

$featured = db()->query('SELECT * FROM products WHERE active = 1 AND featured = 1 ORDER BY created_at DESC LIMIT 8')->fetchAll();
$newArrivals = db()->query('SELECT * FROM products WHERE active = 1 ORDER BY created_at DESC, id DESC LIMIT 4')->fetchAll();
$onSale = db()->query('SELECT * FROM products WHERE active = 1 AND sale_price IS NOT NULL AND sale_price > 0 ORDER BY (price - sale_price) DESC LIMIT 2')->fetchAll();

$pageTitle  = 'Fine wines, delivered';
$activePage = 'home';

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container hero__inner">
        <div class="hero__copy">
            <span class="eyebrow">Est. 2014 · Independent wine merchants</span>
            <h1>Uncork something <em>extraordinary</em>.</h1>
            <p>Hand-picked reds, crisp whites, celebratory bubbles and dessert treasures — sourced from family estates and delivered to your door.</p>
            <div class="hero__actions">
                <a class="btn btn--primary btn--lg" href="<?= url('shop.php') ?>">Shop the collection</a>
                <a class="btn btn--ghost btn--lg" href="<?= url('about.php') ?>">Our story</a>
            </div>
            <ul class="hero__points">
                <li>Free delivery over <?= e(money((float)setting('free_shipping_above', '2499'))) ?></li>
                <li>Sommelier curated</li>
                <li>Secure checkout</li>
            </ul>
        </div>
        <div class="hero__visual" aria-hidden="true">
            <div class="hero__glow"></div>
            <img class="hero__bottle hero__bottle--1" src="<?= asset('img/wine-01.svg') ?>" alt="">
            <img class="hero__bottle hero__bottle--2" src="<?= asset('img/wine-11.svg') ?>" alt="">
            <img class="hero__bottle hero__bottle--3" src="<?= asset('img/wine-13.svg') ?>" alt="">
        </div>
    </div>
</section>

<section class="trust">
    <div class="container trust__grid">
        <div class="trust__item">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v8H3zM14 10h4l3 3v2h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg>
            <div><strong>Free delivery</strong><small>On orders over <?= e(money((float)setting('free_shipping_above', '2499'))) ?></small></div>
        </div>
        <div class="trust__item">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
            <div><strong>Sommelier curated</strong><small>Every bottle tasted &amp; approved</small></div>
        </div>
        <div class="trust__item">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            <div><strong>Secure payments</strong><small>Encrypted checkout, UPI &amp; cards</small></div>
        </div>
        <div class="trust__item">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v18M5 8h14"/><circle cx="12" cy="12" r="9"/></svg>
            <div><strong>Best price promise</strong><small>Found it cheaper? We'll match it</small></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Browse by style</span>
                <h2>Find your perfect bottle</h2>
            </div>
            <a class="link-arrow" href="<?= url('shop.php') ?>">View all wines <span>→</span></a>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a class="category-card" href="<?= url('shop.php?category=' . urlencode($cat['slug'])) ?>">
                    <span class="category-card__count"><?= (int)$cat['product_count'] ?> wines</span>
                    <h3><?= e($cat['name']) ?></h3>
                    <p><?= e($cat['description']) ?></p>
                    <span class="category-card__link">Explore <span>→</span></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($featured): ?>
<section class="section section--tint">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Sommelier's selection</span>
                <h2>Featured this month</h2>
            </div>
            <a class="link-arrow" href="<?= url('shop.php?sort=featured') ?>">See more <span>→</span></a>
        </div>
        <div class="product-grid">
            <?php foreach ($featured as $product): require __DIR__ . '/includes/product-card.php'; endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($onSale): ?>
<section class="section">
    <div class="container promo">
        <div class="promo__copy">
            <span class="eyebrow eyebrow--gold">Limited offer</span>
            <h2>Cellar clear-out — up to <?= max(array_map('product_discount', $onSale)) ?>% off</h2>
            <p>Our sommelier has discounted a small parcel of award-winning bottles. Once they're gone, they're gone.</p>
            <a class="btn btn--primary btn--lg" href="<?= url('shop.php?sale=1') ?>">Shop the sale</a>
        </div>
        <div class="promo__products">
            <?php foreach ($onSale as $product): ?>
                <a class="promo__product" href="<?= url('product.php?slug=' . urlencode($product['slug'])) ?>">
                    <img src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['name']) ?>">
                    <div>
                        <strong><?= e($product['name']) ?></strong>
                        <span class="price"><span class="price__now"><?= e(money(product_price($product))) ?></span> <span class="price__was"><?= e(money((float)$product['price'])) ?></span></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section section--tint">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Just landed</span>
                <h2>New arrivals</h2>
            </div>
        </div>
        <div class="product-grid">
            <?php foreach ($newArrivals as $product): require __DIR__ . '/includes/product-card.php'; endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container newsletter">
        <div>
            <h2>Join the cellar club</h2>
            <p>Rare allocations, tasting invitations and members-only pricing — straight to your inbox. One email a month, no noise.</p>
        </div>
        <form class="newsletter__form" action="<?= url('contact.php') ?>" method="get">
            <input type="email" name="email" placeholder="you@example.com" required aria-label="Email address">
            <button class="btn btn--primary" type="submit">Subscribe</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
