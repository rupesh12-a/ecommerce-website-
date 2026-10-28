<?php
require_once __DIR__ . '/config/config.php';
http_response_code(404);
$pageTitle  = 'Page not found';
$activePage = '';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="empty-state empty-state--big">
        <span class="eyebrow">Error 404</span>
        <h1>This page has been uncorked and poured</h1>
        <p>The page you're looking for doesn't exist, or it moved to a different cellar.</p>
        <div class="empty-state__actions">
            <a class="btn btn--primary" href="<?= url('index.php') ?>">Back home</a>
            <a class="btn btn--ghost" href="<?= url('shop.php') ?>">Shop wines</a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
