<?php
require_once __DIR__ . '/includes/layout.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    switch ($_POST['action'] ?? '') {
        case 'read':
            $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$id]);
            break;
        case 'unread':
            $pdo->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = ?')->execute([$id]);
            break;
        case 'delete':
            $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
            flash('Message deleted.');
            break;
    }
    redirect('admin/messages.php');
}

$filter = (string)($_GET['filter'] ?? '');
$where = $filter === 'unread' ? 'WHERE is_read = 0' : '';
$messages = $pdo->query("SELECT * FROM contact_messages $where ORDER BY created_at DESC")->fetchAll();
$unread = (int)$pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();

admin_header('Messages', 'messages');
?>

<div class="tabs">
    <a class="tab <?= $filter === '' ? 'is-active' : '' ?>" href="<?= url('admin/messages.php') ?>">All</a>
    <a class="tab <?= $filter === 'unread' ? 'is-active' : '' ?>" href="<?= url('admin/messages.php?filter=unread') ?>">Unread <span><?= $unread ?></span></a>
</div>

<?php if (!$messages): ?>
    <section class="panel"><div class="panel__body"><p class="muted">No messages to show.</p></div></section>
<?php else: ?>
    <div class="message-list">
        <?php foreach ($messages as $m): ?>
            <article class="message-card <?= $m['is_read'] ? '' : 'message-card--unread' ?>">
                <header class="message-card__head">
                    <div>
                        <strong><?= e($m['name']) ?></strong>
                        <a href="mailto:<?= e($m['email']) ?>" class="muted">&lt;<?= e($m['email']) ?>&gt;</a>
                    </div>
                    <time class="muted small"><?= e(date('M j, Y g:ia', strtotime($m['created_at']))) ?></time>
                </header>
                <h3><?= e($m['subject']) ?></h3>
                <p><?= nl2br(e($m['message'])) ?></p>
                <footer class="message-card__foot">
                    <a class="btn btn--mini" href="mailto:<?= e($m['email']) ?>?subject=Re: <?= e($m['subject']) ?>">Reply</a>
                    <form method="post" action="<?= url('admin/messages.php') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="<?= $m['is_read'] ? 'unread' : 'read' ?>">
                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <button class="btn btn--mini" type="submit">Mark as <?= $m['is_read'] ? 'unread' : 'read' ?></button>
                    </form>
                    <form method="post" action="<?= url('admin/messages.php') ?>" data-confirm="Delete this message?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <button class="btn btn--mini btn--danger" type="submit">Delete</button>
                    </form>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php admin_footer(); ?>
