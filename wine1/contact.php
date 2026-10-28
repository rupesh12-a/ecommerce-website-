<?php
require_once __DIR__ . '/config/config.php';

$errors = [];
$sent   = false;
$form   = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($form as $key => $_) {
        $form[$key] = trim((string)($_POST[$key] ?? ''));
    }
    if ($form['name'] === '')  $errors['name'] = 'Please tell us your name.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email.';
    if ($form['subject'] === '') $errors['subject'] = 'Please add a subject.';
    if (strlen($form['message']) < 10) $errors['message'] = 'Please write a little more (10+ characters).';

    if (!$errors) {
        $ins = db()->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?,?,?,?)');
        $ins->execute([$form['name'], $form['email'], $form['subject'], $form['message']]);
        flash('Thanks for reaching out — we usually reply within one business day.');
        redirect('contact.php');
    }
}

$pageTitle  = 'Contact';
$activePage = 'contact';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a><span>/</span><span>Contact</span></nav>
    <header class="page-head">
        <div>
            <span class="eyebrow">We'd love to hear from you</span>
            <h1>Get in touch</h1>
            <p>Questions about an order, a wine, or a tasting event? Drop us a line.</p>
        </div>
    </header>

    <div class="contact-layout">
        <form class="card contact-form" method="post" action="<?= url('contact.php') ?>">
            <?= csrf_field() ?>
            <div class="grid-2">
                <label class="field">
                    <span>Your name *</span>
                    <input type="text" name="name" value="<?= e($form['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?><em class="field-error"><?= e($errors['name']) ?></em><?php endif; ?>
                </label>
                <label class="field">
                    <span>Email *</span>
                    <input type="email" name="email" value="<?= e($form['email']) ?>" required>
                    <?php if (isset($errors['email'])): ?><em class="field-error"><?= e($errors['email']) ?></em><?php endif; ?>
                </label>
            </div>
            <label class="field">
                <span>Subject *</span>
                <input type="text" name="subject" value="<?= e($form['subject']) ?>" required>
                <?php if (isset($errors['subject'])): ?><em class="field-error"><?= e($errors['subject']) ?></em><?php endif; ?>
            </label>
            <label class="field">
                <span>Message *</span>
                <textarea name="message" rows="6" required><?= e($form['message']) ?></textarea>
                <?php if (isset($errors['message'])): ?><em class="field-error"><?= e($errors['message']) ?></em><?php endif; ?>
            </label>
            <button class="btn btn--primary btn--lg" type="submit">Send message</button>
        </form>

        <aside class="contact-info">
            <div class="card">
                <h3>Visit the cellar</h3>
                <p><?= e(setting('shop_address', '')) ?></p>
                <h3>Talk to us</h3>
                <p><a href="mailto:<?= e(setting('shop_email', '')) ?>"><?= e(setting('shop_email', '')) ?></a><br><?= e(setting('shop_phone', '')) ?></p>
                <h3>Opening hours</h3>
                <p>Mon–Sat 10:00 – 20:00<br>Sunday 12:00 – 18:00</p>
            </div>
            <div class="card card--tint">
                <h3>Trade &amp; events</h3>
                <p>Restaurants, weddings and corporate gifting — we'll build a bespoke list for you.</p>
                <p><a href="mailto:<?= e(setting('shop_email', '')) ?>">Enquire about events →</a></p>
            </div>
        </aside>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
