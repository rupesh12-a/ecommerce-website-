<?php
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '') $errors['email'] = 'Enter your email address.';
    if ($password === '') $errors['password'] = 'Enter your password.';

    if (!$errors) {
        $stmt = db()->prepare('SELECT id, name, password, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $account = $stmt->fetch();

        if ($account && password_verify($password, $account['password'])) {
            $_SESSION['user_id'] = (int)$account['id'];
            session_regenerate_id(true);
            flash('Welcome back, ' . $account['name'] . '!');

            if ($account['role'] === 'admin' && !empty($_SESSION['redirect_after_login']) && str_contains($_SESSION['redirect_after_login'], '/admin')) {
                $target = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                redirect($target);
            }
            $target = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($target);
        }
        $errors['general'] = 'Those credentials do not match our records.';
    }
}

$pageTitle  = 'Sign in';
$activePage = '';
require __DIR__ . '/includes/header.php';
?>

<div class="container auth-wrap">
    <div class="auth-card">
        <div class="auth-card__aside">
            <h2>Welcome back</h2>
            <p>Sign in to track orders, save your favourites and check out faster.</p>
            <ul class="tick-list">
                <li>Order history &amp; tracking</li>
                <li>Faster checkout</li>
                <li>Members-only offers</li>
            </ul>
        </div>
        <div class="auth-card__form">
            <h1>Sign in</h1>
            <?php if (!empty($errors['general'])): ?><div class="alert alert--error"><?= e($errors['general']) ?></div><?php endif; ?>
            <form method="post" action="<?= url('login.php') ?>">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= e($email) ?>" required autofocus>
                    <?php if (isset($errors['email'])): ?><em class="field-error"><?= e($errors['email']) ?></em><?php endif; ?>
                </label>
                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" required>
                    <?php if (isset($errors['password'])): ?><em class="field-error"><?= e($errors['password']) ?></em><?php endif; ?>
                </label>
                <button class="btn btn--primary btn--lg btn--block" type="submit">Sign in</button>
            </form>
            <p class="auth-switch">New here? <a href="<?= url('register.php') ?>">Create an account</a></p>
            <div class="demo-hint">
                <strong>Demo logins</strong>
                <span>Admin — admin@vinoecasa.com / admin123</span>
                <span>Customer — priya@example.com / customer123</span>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
