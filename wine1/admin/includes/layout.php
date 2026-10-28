<?php
/**
 * Admin shell — sidebar + topbar.
 * Call admin_header('Dashboard', 'dashboard'); ... admin_footer();
 */

require_once __DIR__ . '/../../config/config.php';
require_admin();

function admin_nav(): array
{
    return [
        'dashboard'  => ['Dashboard', 'admin/index.php', '<path d="M4 13h6V4H4zM14 20h6v-9h-6zM4 20h6v-4H4zM14 8h6V4h-6z"/>'],
        'products'   => ['Products', 'admin/products.php', '<path d="M4 7h16v13H4zM8 7V4h8v3"/>'],
        'categories' => ['Categories', 'admin/categories.php', '<path d="M4 5h7v7H4zM13 5h7v7h-7zM4 14h7v5H4zM13 14h7v5h-7z"/>'],
        'orders'     => ['Orders', 'admin/orders.php', '<path d="M6 4h12v16l-6-3-6 3z"/>'],
        'customers'  => ['Customers', 'admin/customers.php', '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M17 11a3 3 0 1 0 0-6"/>'],
        'messages'   => ['Messages', 'admin/messages.php', '<path d="M4 5h16v12H8l-4 4z"/>'],
        'settings'   => ['Settings', 'admin/settings.php', '<circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M6 6l1.5 1.5M16.5 16.5 18 18M18 6l-1.5 1.5M7.5 16.5 6 18"/>'],
    ];
}

function admin_header(string $title, string $active = ''): void
{
    $user = current_user();

    $pendingOrders = 0;
    $unreadMessages = 0;
    try {
        $pendingOrders = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','processing')")->fetchColumn();
        $unreadMessages = (int)db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
    } catch (Throwable $e) {
    }
    $counts = ['orders' => $pendingOrders, 'messages' => $unreadMessages];
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> · Admin — <?= e(setting('shop_name', 'Vino e Casa')) ?></title>
        <link rel="icon" href="<?= asset('img/logo.svg') ?>" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    </head>
    <body class="admin">
    <a class="skip-link" href="#admin-main">Skip to content</a>

    <aside class="sidebar" data-sidebar>
        <div class="sidebar__brand">
            <img src="<?= asset('img/logo.svg') ?>" alt="" width="34" height="34">
            <span><strong><?= e(setting('shop_name', 'Vino e Casa')) ?></strong><small>Admin panel</small></span>
        </div>
        <nav class="sidebar__nav">
            <?php foreach (admin_nav() as $key => [$label, $href, $icon]): ?>
                <a href="<?= url($href) ?>" class="<?= $active === $key ? 'is-active' : '' ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg>
                    <span><?= e($label) ?></span>
                    <?php if (isset($counts[$key]) && $counts[$key] > 0): ?><b class="nav-count"><?= (int)$counts[$key] ?></b><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar__foot">
            <a href="<?= url('index.php') ?>" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/></svg>
                View store
            </a>
            <a href="<?= url('admin/logout.php') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 12H3M6 8l-3 4 3 4"/></svg>
                Sign out
            </a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="topbar">
            <button class="icon-btn" type="button" data-sidebar-toggle aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <h1><?= e($title) ?></h1>
            <div class="topbar__user">
                <span class="avatar"><?= e(mb_substr($user['name'], 0, 1)) ?></span>
                <div><strong><?= e($user['name']) ?></strong><small><?= e($user['email']) ?></small></div>
            </div>
        </header>

        <div class="toast-stack" data-toast-stack>
            <?php foreach (take_flashes() as $f): ?>
                <div class="toast toast--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
            <?php endforeach; ?>
        </div>

        <main class="admin-content" id="admin-main">
<?php
}

function admin_footer(): void
{
    ?>
        </main>
    </div>
    <script>window.APP = { base: '<?= e(BASE_URL) ?>', csrf: '<?= e(csrf_token()) ?>' };</script>
    <script src="<?= asset('js/admin.js') ?>" defer></script>
    </body>
    </html>
<?php
}
