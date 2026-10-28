<?php
require_once __DIR__ . '/includes/layout.php';

$pdo = db();

$editable = [
    'shop_name'           => ['Store name', 'text'],
    'tagline'             => ['Tagline', 'text'],
    'shop_email'          => ['Contact email', 'email'],
    'shop_phone'          => ['Phone', 'text'],
    'shop_address'        => ['Address', 'textarea'],
    'currency_symbol'     => ['Currency symbol', 'text'],
    'shipping_fee'        => ['Delivery fee', 'number'],
    'free_shipping_above' => ['Free delivery above', 'number'],
    'gst_percent'         => ['Tax (%)', 'number'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (($_POST['action'] ?? '') === 'settings') {
        $upsert = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach (array_keys($editable) as $key) {
            $upsert->execute([$key, trim((string)($_POST[$key] ?? ''))]);
        }
        flash('Store settings saved.');
    } elseif (($_POST['action'] ?? '') === 'password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['new_password_confirm'] ?? '');
        $user    = current_user();
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hash = (string)$stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            flash('Your current password is incorrect.', 'error');
        } elseif (strlen($new) < 6) {
            flash('New password must be at least 6 characters.', 'error');
        } elseif ($new !== $confirm) {
            flash('New passwords do not match.', 'error');
        } else {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('Password updated.');
        }
    }
    redirect('admin/settings.php');
}

$current = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings') as $row) {
    $current[$row['setting_key']] = $row['setting_value'];
}

admin_header('Settings', 'settings');
?>

<div class="admin-columns admin-columns--aside">
    <section class="panel">
        <header class="panel__head"><h2>Store settings</h2></header>
        <div class="panel__body">
            <form method="post" action="<?= url('admin/settings.php') ?>" class="grid-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="settings">
                <?php foreach ($editable as $key => [$label, $type]): ?>
                    <label class="field <?= $type === 'textarea' ? 'field--full' : '' ?>">
                        <span><?= e($label) ?></span>
                        <?php if ($type === 'textarea'): ?>
                            <textarea name="<?= e($key) ?>" rows="2"><?= e($current[$key] ?? '') ?></textarea>
                        <?php else: ?>
                            <input type="<?= e($type) ?>" name="<?= e($key) ?>" step="any" value="<?= e($current[$key] ?? '') ?>">
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
                <div class="field--full"><button class="btn btn--primary btn--lg" type="submit">Save settings</button></div>
            </form>
        </div>
    </section>

    <aside class="stack">
        <section class="panel">
            <header class="panel__head"><h2>Change password</h2></header>
            <div class="panel__body">
                <form method="post" action="<?= url('admin/settings.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">
                    <label class="field"><span>Current password</span><input type="password" name="current_password" required></label>
                    <label class="field"><span>New password</span><input type="password" name="new_password" required></label>
                    <label class="field"><span>Confirm new password</span><input type="password" name="new_password_confirm" required></label>
                    <button class="btn btn--primary btn--block" type="submit">Update password</button>
                </form>
            </div>
        </section>

        <section class="panel">
            <header class="panel__head"><h2>System</h2></header>
            <div class="panel__body">
                <ul class="kv-list">
                    <li><span>PHP version</span><strong><?= e(PHP_VERSION) ?></strong></li>
                    <li><span>Database</span><strong><?= e(DB_NAME) ?></strong></li>
                    <li><span>Server</span><strong><?= e($_SERVER['SERVER_SOFTWARE'] ?? 'PHP built-in') ?></strong></li>
                    <li><span>Logged in as</span><strong><?= e(current_user()['email']) ?></strong></li>
                </ul>
            </div>
        </section>
    </aside>
</div>

<?php admin_footer(); ?>
