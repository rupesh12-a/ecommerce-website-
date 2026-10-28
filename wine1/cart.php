<?php
require_once __DIR__ . '/config/config.php';

$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    require_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty       = max(1, (int)($_POST['qty'] ?? 1));

    if ($productId > 0 && $action !== 'clear') {
        $chk = db()->prepare('SELECT id, stock FROM products WHERE id = ? AND active = 1');
        $chk->execute([$productId]);
        $prod = $chk->fetch();
    }

    switch ($action) {
        case 'add':
            if (!empty($prod)) {
                $max = max(1, (int)$prod['stock']);
                $current = cart()[$productId] ?? 0;
                cart_set($productId, min($current + $qty, $max));
            }
            break;
        case 'update':
            if (!empty($prod)) {
                cart_set($productId, min($qty, max(1, (int)$prod['stock'])));
            }
            break;
        case 'remove':
            cart_remove($productId);
            break;
        case 'clear':
            cart_clear();
            break;
    }

    if (is_ajax()) {
        $items = array_map(fn($i) => [
            'id'    => (int)$i['id'],
            'qty'   => (int)$i['qty'],
            'line'  => money($i['line']),
        ], cart_items());
        $totals = cart_totals();
        json_response([
            'ok'        => true,
            'count'     => cart_count(),
            'subtotal'  => money($totals['subtotal']),
            'shipping'  => money($totals['shipping']),
            'total'     => money($totals['total']),
            'freeAbove' => $totals['shipping'] == 0 && $totals['subtotal'] > 0,
            'items'     => $items,
        ]);
    }

    redirect('cart.php');
}

$items  = cart_items();
$totals = cart_totals();
$freeAbove = (float)setting('free_shipping_above', '2499');
$remaining = max(0, $freeAbove - $totals['subtotal']);

$pageTitle  = 'Your cart';
$activePage = 'cart';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a><span>/</span><span>Cart</span></nav>
    <header class="page-head"><h1>Your cart</h1><p><?= $totals['count'] ?> item<?= $totals['count'] === 1 ? '' : 's' ?></p></header>

    <?php if (!$items): ?>
        <div class="empty-state">
            <h3>Your cart is empty</h3>
            <p>Browse the cellar and add a bottle or two.</p>
            <a class="btn btn--primary" href="<?= url('shop.php') ?>">Shop wines</a>
        </div>
    <?php else: ?>
        <?php if ($remaining > 0): ?>
            <div class="ship-progress">
                <p>Add <strong><?= e(money($remaining)) ?></strong> more for free delivery.</p>
                <div class="ship-progress__bar"><span style="width: <?= min(100, ($totals['subtotal'] / $freeAbove) * 100) ?>%"></span></div>
            </div>
        <?php else: ?>
            <div class="ship-progress ship-progress--done"><p>🎉 You've unlocked free delivery.</p></div>
        <?php endif; ?>

        <div class="cart-layout">
            <div class="cart-items" data-cart-items>
                <?php foreach ($items as $item): ?>
                    <article class="cart-item" data-product-id="<?= (int)$item['id'] ?>">
                        <a class="cart-item__media" href="<?= url('product.php?slug=' . urlencode($item['slug'])) ?>">
                            <img src="<?= e(product_image($item['image'])) ?>" alt="<?= e($item['name']) ?>">
                        </a>
                        <div class="cart-item__info">
                            <h3><a href="<?= url('product.php?slug=' . urlencode($item['slug'])) ?>"><?= e($item['name']) ?></a></h3>
                            <p class="muted"><?= e(money($item['unit'])) ?> each</p>
                            <form class="cart-item__controls js-cart-update" action="<?= url('cart.php') ?>" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                                <div class="qty qty--sm">
                                    <button type="button" data-qty="-1" aria-label="Decrease">−</button>
                                    <input type="number" name="qty" value="<?= (int)$item['qty'] ?>" min="1" max="<?= (int)$item['stock'] ?>" aria-label="Quantity">
                                    <button type="button" data-qty="1" aria-label="Increase">+</button>
                                </div>
                            </form>
                            <button class="link-danger js-cart-remove" type="button" data-product-id="<?= (int)$item['id'] ?>">Remove</button>
                        </div>
                        <div class="cart-item__price" data-line-total><?= e(money($item['line'])) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="cart-summary">
                <h2>Order summary</h2>
                <dl class="summary-list">
                    <div><dt>Subtotal</dt><dd data-summary-subtotal><?= e(money($totals['subtotal'])) ?></dd></div>
                    <div><dt>Delivery</dt><dd data-summary-shipping><?= $totals['shipping'] > 0 ? e(money($totals['shipping'])) : 'Free' ?></dd></div>
                    <div class="summary-list__total"><dt>Total</dt><dd data-summary-total><?= e(money($totals['total'])) ?></dd></div>
                </dl>
                <a class="btn btn--primary btn--lg btn--block" href="<?= url('checkout.php') ?>">Proceed to checkout</a>
                <a class="btn btn--ghost btn--block" href="<?= url('shop.php') ?>">Continue shopping</a>
                <button class="link-danger js-cart-clear" type="button" data-cart-clear>Clear cart</button>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
