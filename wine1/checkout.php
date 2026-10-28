<?php
require_once __DIR__ . '/config/config.php';

$items = cart_items();
if (!$items) {
    flash('Your cart is empty.', 'warning');
    redirect('cart.php');
}

$totals = cart_totals();
$user   = current_user();
$errors = [];

$form = [
    'full_name'      => $user['name'] ?? '',
    'email'          => $user['email'] ?? '',
    'phone'          => $user['phone'] ?? '',
    'address'        => '',
    'city'           => '',
    'postal_code'    => '',
    'country'        => 'India',
    'notes'          => '',
    'payment_method' => 'cod',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($form as $key => $default) {
        $form[$key] = trim((string)($_POST[$key] ?? $default));
    }

    if ($form['full_name'] === '')  $errors['full_name'] = 'Please enter your full name.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    if (strlen($form['phone']) < 6) $errors['phone'] = 'Please enter a contact number.';
    if ($form['address'] === '')    $errors['address'] = 'Please enter a delivery address.';
    if ($form['city'] === '')       $errors['city'] = 'Please enter a city.';
    if (!in_array($form['payment_method'], ['cod', 'card', 'upi'], true)) $form['payment_method'] = 'cod';

    // Recompute totals server-side — never trust the browser.
    $totals = cart_totals();

    if (!$errors) {
        try {
            $orderId = create_order([
                'user_id'        => $user['id'] ?? null,
                'full_name'      => $form['full_name'],
                'email'          => $form['email'],
                'phone'          => $form['phone'],
                'address'        => $form['address'],
                'city'           => $form['city'],
                'postal_code'    => $form['postal_code'],
                'country'        => $form['country'] ?: 'India',
                'notes'          => $form['notes'],
                'payment_method' => $form['payment_method'],
            ], $items, $totals);

            $_SESSION['last_order'] = $orderId;
            cart_clear();
            redirect('order-success.php?id=' . $orderId);
        } catch (Throwable $e) {
            $errors['general'] = 'We could not place your order. Please try again.';
        }
    }
}

$pageTitle  = 'Checkout';
$activePage = 'cart';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a><span>/</span><a href="<?= url('cart.php') ?>">Cart</a><span>/</span><span>Checkout</span></nav>
    <header class="page-head"><h1>Checkout</h1><p>You're moments away from a very good evening.</p></header>

    <?php if (!empty($errors['general'])): ?><div class="alert alert--error"><?= e($errors['general']) ?></div><?php endif; ?>

    <form class="checkout-layout" method="post" action="<?= url('checkout.php') ?>">
        <?= csrf_field() ?>

        <div class="checkout-form">
            <section class="card">
                <h2>Delivery details</h2>
                <div class="grid-2">
                    <label class="field">
                        <span>Full name *</span>
                        <input type="text" name="full_name" value="<?= e($form['full_name']) ?>" required>
                        <?php if (isset($errors['full_name'])): ?><em class="field-error"><?= e($errors['full_name']) ?></em><?php endif; ?>
                    </label>
                    <label class="field">
                        <span>Email *</span>
                        <input type="email" name="email" value="<?= e($form['email']) ?>" required>
                        <?php if (isset($errors['email'])): ?><em class="field-error"><?= e($errors['email']) ?></em><?php endif; ?>
                    </label>
                    <label class="field">
                        <span>Phone *</span>
                        <input type="tel" name="phone" value="<?= e($form['phone']) ?>" placeholder="+91 …" required>
                        <?php if (isset($errors['phone'])): ?><em class="field-error"><?= e($errors['phone']) ?></em><?php endif; ?>
                    </label>
                    <label class="field">
                        <span>City *</span>
                        <input type="text" name="city" value="<?= e($form['city']) ?>" required>
                        <?php if (isset($errors['city'])): ?><em class="field-error"><?= e($errors['city']) ?></em><?php endif; ?>
                    </label>
                    <label class="field field--full">
                        <span>Delivery address *</span>
                        <input type="text" name="address" value="<?= e($form['address']) ?>" placeholder="House / street / area" required>
                        <?php if (isset($errors['address'])): ?><em class="field-error"><?= e($errors['address']) ?></em><?php endif; ?>
                    </label>
                    <label class="field">
                        <span>Postal code</span>
                        <input type="text" name="postal_code" value="<?= e($form['postal_code']) ?>">
                    </label>
                    <label class="field">
                        <span>Country</span>
                        <input type="text" name="country" value="<?= e($form['country']) ?>">
                    </label>
                    <label class="field field--full">
                        <span>Order notes (optional)</span>
                        <textarea name="notes" rows="3" placeholder="Gate code, gift message, preferred time…"><?= e($form['notes']) ?></textarea>
                    </label>
                </div>
            </section>

            <section class="card">
                <h2>Payment method</h2>
                <div class="pay-options">
                    <label class="pay-option">
                        <input type="radio" name="payment_method" value="cod" <?= $form['payment_method'] === 'cod' ? 'checked' : '' ?>>
                        <span><strong>Cash on delivery</strong><small>Pay when your wine arrives</small></span>
                    </label>
                    <label class="pay-option">
                        <input type="radio" name="payment_method" value="upi" <?= $form['payment_method'] === 'upi' ? 'checked' : '' ?>>
                        <span><strong>UPI</strong><small>GPay, PhonePe, Paytm</small></span>
                    </label>
                    <label class="pay-option">
                        <input type="radio" name="payment_method" value="card" <?= $form['payment_method'] === 'card' ? 'checked' : '' ?>>
                        <span><strong>Card</strong><small>Credit / debit, secured</small></span>
                    </label>
                </div>
                <p class="muted small">This is a demo store — no real payment is processed.</p>
            </section>
        </div>

        <aside class="checkout-summary">
            <h2>Your order</h2>
            <ul class="mini-cart">
                <?php foreach ($items as $item): ?>
                    <li>
                        <img src="<?= e(product_image($item['image'])) ?>" alt="">
                        <div>
                            <strong><?= e($item['name']) ?></strong>
                            <small>Qty <?= (int)$item['qty'] ?> · <?= e(money($item['unit'])) ?></small>
                        </div>
                        <span><?= e(money($item['line'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <dl class="summary-list">
                <div><dt>Subtotal</dt><dd><?= e(money($totals['subtotal'])) ?></dd></div>
                <div><dt>Delivery</dt><dd><?= $totals['shipping'] > 0 ? e(money($totals['shipping'])) : 'Free' ?></dd></div>
                <div class="summary-list__total"><dt>Total</dt><dd><?= e(money($totals['total'])) ?></dd></div>
            </dl>
            <button class="btn btn--primary btn--lg btn--block" type="submit">Place order</button>
            <a class="btn btn--ghost btn--block" href="<?= url('cart.php') ?>">Back to cart</a>
        </aside>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
