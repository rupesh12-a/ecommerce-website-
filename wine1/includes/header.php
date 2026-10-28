<?php
/**
 * Storefront layout — <head>, announcement bar, navigation.
 *
 * Expected (optional) variables before include:
 *   $pageTitle  string
 *   $activePage string  home|shop|product|about|contact|cart
 */

require_once __DIR__ . '/../config/config.php';

$pageTitle  = $pageTitle  ?? 'Fine wines, delivered';
$activePage = $activePage ?? '';
$user       = current_user();
$cartCount  = cart_count();

$navLinks = [
    'home'    => ['label' => 'Home',    'href' => 'index.php'],
    'shop'    => ['label' => 'Shop',    'href' => 'shop.php'],
    'about'   => ['label' => 'About',   'href' => 'about.php'],
    'contact' => ['label' => 'Contact', 'href' => 'contact.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(setting('shop_name', 'Vino e Casa')) ?></title>
    <meta name="description" content="<?= e(setting('tagline', 'Curated wines, delivered to your door')) ?>">
    <link rel="icon" href="<?= asset('img/logo.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="page-<?= e($activePage) ?>">
<a class="skip-link" href="#main">Skip to content</a>

<div class="announce">
    <div class="container announce__inner">
        <span>Free delivery on orders over <?= e(money((float)setting('free_shipping_above', '2499'))) ?></span>
        <span class="announce__sep"></span>
        <span>Curated by our in-house sommelier</span>
    </div>
</div>

<header class="site-header" id="siteHeader">
    <div class="container site-header__inner">
        <button class="icon-btn nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>

        <a class="brand" href="<?= url('index.php') ?>">
            <img src="<?= asset('img/logo.svg') ?>" alt="" width="40" height="40">
            <span class="brand__text">
                <strong><?= e(setting('shop_name', 'Vino e Casa')) ?></strong>
                <small>Wine Merchants</small>
            </span>
        </a>

        <nav class="main-nav" data-nav>
            <?php foreach ($navLinks as $key => $link): ?>
                <a class="<?= $activePage === $key ? 'is-active' : '' ?>" href="<?= url($link['href']) ?>"><?= e($link['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <form class="search" action="<?= url('shop.php') ?>" method="get" role="search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" placeholder="Search wines…" value="<?= e($_GET['q'] ?? '') ?>" aria-label="Search wines">
            </form>

            <div class="account-menu">
                <button class="icon-btn" type="button" aria-haspopup="true" aria-expanded="false" data-dropdown-toggle aria-label="Account">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.5 3-6 7-6s7 2.5 7 6"/></svg>
                </button>
                <div class="dropdown" data-dropdown>
                    <?php if ($user): ?>
                        <div class="dropdown__head">
                            <strong><?= e($user['name']) ?></strong>
                            <small><?= e($user['email']) ?></small>
                        </div>
                        <a href="<?= url('my-orders.php') ?>">My orders</a>
                        <?php if (is_admin()): ?><a href="<?= url('admin/index.php') ?>">Admin panel</a><?php endif; ?>
                        <a href="<?= url('logout.php') ?>">Sign out</a>
                    <?php else: ?>
                        <div class="dropdown__head"><strong>Welcome</strong><small>Sign in for faster checkout</small></div>
                        <a href="<?= url('login.php') ?>">Sign in</a>
                        <a href="<?= url('register.php') ?>">Create account</a>
                    <?php endif; ?>
                </div>
            </div>

            <a class="icon-btn cart-btn" href="<?= url('cart.php') ?>" aria-label="Cart">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M6 6 5 3H3"/></svg>
                <span class="cart-count" data-cart-count <?= $cartCount ? '' : 'hidden' ?>><?= $cartCount ?></span>
            </a>
        </div>
    </div>
</header>

<div class="toast-stack" data-toast-stack>
    <?php foreach (take_flashes() as $f): ?>
        <div class="toast toast--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
</div>

<main id="main">
