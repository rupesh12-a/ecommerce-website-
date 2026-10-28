<?php
require_once __DIR__ . '/../config/config.php';

if (is_admin()) {
    redirect('admin/index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT id, name, password, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $account = $stmt->fetch();

    if ($account && password_verify($password, $account['password'])) {
        if ($account['role'] !== 'admin') {
            $error = 'This account does not have admin access.';
        } else {
            $_SESSION['user_id'] = (int)$account['id'];
            session_regenerate_id(true);
            flash('Welcome back, ' . $account['name'] . '.');
            redirect('admin/index.php');
        }
    } else {
        $error = 'Incorrect email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin sign in — <?= e(setting('shop_name', 'Vino e Casa')) ?></title>
    <link rel="icon" href="<?= asset('img/logo.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin-login-body">
    <div class="admin-login">
        <div class="admin-login__card">
            <div class="admin-login__brand">
                <img src="<?= asset('img/logo.svg') ?>" alt="" width="46" height="46">
                <h1><?= e(setting('shop_name', 'Vino e Casa')) ?></h1>
                <p>Admin control panel</p>
            </div>

            <?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

            <form method="post" action="<?= url('admin/login.php') ?>">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= e($email) ?>" required autofocus>
                </label>
                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" required>
                </label>
                <button class="btn btn--primary btn--lg btn--block" type="submit">Sign in</button>
            </form>

            <div class="admin-login__hint">
                Demo admin — <strong>admin@vinoecasa.com</strong> / <strong>admin123</strong>
            </div>
            <a class="admin-login__back" href="<?= url('index.php') ?>">← Back to store</a>
        </div>
    </div>
</body>
</html>
