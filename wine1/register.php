<?php
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$form = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($form as $key => $_) {
        $form[$key] = trim((string)($_POST[$key] ?? ''));
    }
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');

    if ($form['name'] === '') $errors['name'] = 'Please enter your name.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email.';
    if (strlen($password) < 6) $errors['password'] = 'Use at least 6 characters.';
    if ($password !== $confirm) $errors['password_confirm'] = 'Passwords do not match.';

    if (!$errors) {
        $chk = db()->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$form['email']]);
        if ($chk->fetch()) {
            $errors['email'] = 'That email is already registered.';
        } else {
            $ins = db()->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?,?,?,?,"customer")');
            $ins->execute([$form['name'], $form['email'], password_hash($password, PASSWORD_DEFAULT), $form['phone'] ?: null]);
            $_SESSION['user_id'] = (int)db()->lastInsertId();
            session_regenerate_id(true);
            flash('Welcome to ' . setting('shop_name') . ', ' . $form['name'] . '!');
            redirect('index.php');
        }
    }
}

$pageTitle  = 'Create account';
$activePage = '';
require __DIR__ . '/includes/header.php';
?>

<div class="container auth-wrap">
    <div class="auth-card">
        <div class="auth-card__aside">
            <h2>Join the cellar club</h2>
            <p>Create a free account to save favourites, track orders and get first access to rare allocations.</p>
            <ul class="tick-list">
                <li>Personal order history</li>
                <li>Faster checkout</li>
                <li>Exclusive member pricing</li>
            </ul>
        </div>
        <div class="auth-card__form">
            <h1>Create account</h1>
            <form method="post" action="<?= url('register.php') ?>">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Full name</span>
                    <input type="text" name="name" value="<?= e($form['name']) ?>" required autofocus>
                    <?php if (isset($errors['name'])): ?><em class="field-error"><?= e($errors['name']) ?></em><?php endif; ?>
                </label>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= e($form['email']) ?>" required>
                    <?php if (isset($errors['email'])): ?><em class="field-error"><?= e($errors['email']) ?></em><?php endif; ?>
                </label>
                <label class="field">
                    <span>Phone (optional)</span>
                    <input type="tel" name="phone" value="<?= e($form['phone']) ?>">
                </label>
                <div class="grid-2">
                    <label class="field">
                        <span>Password</span>
                        <input type="password" name="password" required>
                        <?php if (isset($errors['password'])): ?><em class="field-error"><?= e($errors['password']) ?></em><?php endif; ?>
                    </label>
                    <label class="field">
                        <span>Confirm password</span>
                        <input type="password" name="password_confirm" required>
                        <?php if (isset($errors['password_confirm'])): ?><em class="field-error"><?= e($errors['password_confirm']) ?></em><?php endif; ?>
                    </label>
                </div>
                <button class="btn btn--primary btn--lg btn--block" type="submit">Create account</button>
            </form>
            <p class="auth-switch">Already have an account? <a href="<?= url('login.php') ?>">Sign in</a></p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
