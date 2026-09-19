<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['student']);

$pdo = getDBConnection();
$complaintId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT c.*, cat.name AS category_name, d.name AS department_name
     FROM complaints c
     JOIN complaint_categories cat ON cat.category_id = c.category_id
     LEFT JOIN departments d ON d.department_id = c.department_id
     WHERE c.complaint_id = :id AND c.student_id = :sid"
);
$stmt->execute(['id' => $complaintId, 'sid' => $_SESSION['user_id']]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('error', 'Complaint not found.');
    redirect('/student/my_complaints.php');
}

// Status history
$stmt = $pdo->prepare(
    "SELECT h.*, u.full_name AS changed_by_name
     FROM complaint_status_history h
     LEFT JOIN users u ON u.user_id = h.changed_by
     WHERE h.complaint_id = :id
     ORDER BY h.changed_at ASC"
);
$stmt->execute(['id' => $complaintId]);
$history = $stmt->fetchAll();

// Attachments
$stmt = $pdo->prepare("SELECT * FROM complaint_attachments WHERE complaint_id = :id");
$stmt->execute(['id' => $complaintId]);
$attachments = $stmt->fetchAll();

$pageTitle = 'Complaint Detail';
require_once __DIR__ . '/../includes/header.php';
?>

<h1><?= e($complaint['title']) ?></h1>
<p class="tracking-code">Tracking Code: <strong><?= e($complaint['tracking_code']) ?></strong></p>

<div class="detail-card">
    <p><strong>Status:</strong> <span class="badge-status badge-<?= e($complaint['status']) ?>"><?= e(str_replace('_', ' ', $complaint['status'])) ?></span></p>
    <p><strong>Category:</strong> <?= e($complaint['category_name']) ?></p>
    <p><strong>Department:</strong> <?= e($complaint['department_name'] ?? 'Not yet assigned') ?></p>
    <p><strong>Priority:</strong> <?= e(ucfirst($complaint['priority'])) ?></p>
    <p><strong>Submitted:</strong> <?= e(date('M j, Y g:i A', strtotime($complaint['created_at']))) ?></p>
    <p><strong>Description:</strong></p>
    <p><?= nl2br(e($complaint['description'])) ?></p>

    <?php if ($complaint['resolution_note']): ?>
        <p><strong>Resolution Note:</strong></p>
        <p><?= nl2br(e($complaint['resolution_note'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($attachments)): ?>
        <p><strong>Attachments:</strong></p>
        <ul>
            <?php foreach ($attachments as $a): ?>
                <li><a href="<?= url('/' . $a['file_path']) ?>" target="_blank"><?= e($a['original_name']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<h2>Status History</h2>
<ul class="timeline">
    <?php foreach ($history as $h): ?>
        <li>
            <strong><?= e(str_replace('_', ' ', $h['new_status'])) ?></strong>
            — <?= e(date('M j, Y g:i A', strtotime($h['changed_at']))) ?>
            <?php if ($h['remarks']): ?><br><em><?= e($h['remarks']) ?></em><?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>

<p><a href="<?= url('/student/my_complaints.php') ?>">&larr; Back to My Complaints</a></p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
