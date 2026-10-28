<?php
/** Renders a single product card. Expects $product (row from products table). */
$discount = product_discount($product);
$outOfStock = (int)$product['stock'] <= 0;
?>
<article class="product-card">
    <a class="product-card__media" href="<?= url('product.php?slug=' . urlencode($product['slug'])) ?>">
        <img src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        <?php if ($discount): ?><span class="pill pill--sale">-<?= $discount ?>%</span><?php endif; ?>
        <?php if ($outOfStock): ?><span class="pill pill--muted">Sold out</span><?php endif; ?>
    </a>
    <div class="product-card__body">
        <p class="product-card__meta">
            <?= e($product['region'] ?? '') ?><?= !empty($product['vintage']) ? ' · ' . e((string)$product['vintage']) : '' ?>
        </p>
        <h3 class="product-card__title">
            <a href="<?= url('product.php?slug=' . urlencode($product['slug'])) ?>"><?= e($product['name']) ?></a>
        </h3>
        <p class="product-card__grape"><?= e($product['grape'] ?? '') ?></p>
        <div class="product-card__foot">
            <div class="price">
                <span class="price__now"><?= e(money(product_price($product))) ?></span>
                <?php if ($discount): ?><span class="price__was"><?= e(money((float)$product['price'])) ?></span><?php endif; ?>
            </div>
            <form class="js-add-to-cart" action="<?= url('cart.php') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <input type="hidden" name="qty" value="1">
                <button class="btn btn--icon" type="submit" <?= $outOfStock ? 'disabled' : '' ?> aria-label="Add <?= e($product['name']) ?> to cart">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                </button>
            </form>
        </div>
    </div>
</article>
