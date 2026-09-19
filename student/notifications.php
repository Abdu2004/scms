<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['student']);

$pdo = getDBConnection();

// Only mark notifications as read when the user explicitly requests it
if (isset($_GET['read']) && $_GET['read'] === '1') {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid")->execute(['uid' => $_SESSION['user_id']]);
    redirect('/student/notifications.php');
}

$stmt = $pdo->prepare(
    "SELECT n.*, c.tracking_code
     FROM notifications n
     LEFT JOIN complaints c ON c.complaint_id = n.complaint_id
     WHERE n.user_id = :uid
     ORDER BY n.created_at DESC
     LIMIT 50"
);
$stmt->execute(['uid' => $_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Notifications</h1>

<?php if (!empty($notifications)): ?>
    <div style="margin-bottom: 1rem;">
        <a href="<?= url('/student/notifications.php?read=1') ?>" class="btn-link">Mark all as read</a>
    </div>
<?php endif; ?>

<?php if (empty($notifications)): ?>
    <p>You have no notifications yet.</p>
<?php else: ?>
    <ul class="notif-list">
        <?php foreach ($notifications as $n): ?>
            <li>
                <p><?= e($n['message']) ?></p>
                <span class="notif-meta">
                    <?= e(date('M j, Y g:i A', strtotime($n['created_at']))) ?>
                    <?php if ($n['tracking_code']): ?>
                        — <a href="<?= url('/student/complaint_detail.php') ?>?id=<?= (int) $n['complaint_id'] ?>">View complaint</a>
                    <?php endif; ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
