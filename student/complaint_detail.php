<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['student']);
$pdo = getDBConnection();
$complaintId = (int) ($_GET['id'] ?? 0);

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    verifyCsrfOrRedirect();
    $msg = trim($_POST['reply_message'] ?? '');
    if ($msg !== '') {
        $stmt = $pdo->prepare("INSERT INTO complaint_replies (complaint_id, user_id, message, is_admin) VALUES (:cid, :uid, :msg, 0)");
        $stmt->execute(['cid' => $complaintId, 'uid' => $_SESSION['user_id'], 'msg' => $msg]);
        
        // Notify admin
        if ($complaint['assigned_admin_id']) {
            notifyUser($pdo, (int)$complaint['assigned_admin_id'], "Student replied to complaint {$complaint['tracking_code']}.", $complaintId, 'in_app');
        }
        redirect('/student/complaint_detail.php?id=' . $complaintId);
    }
}

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

// Fetch replies
$stmt = $pdo->prepare("SELECT r.*, u.full_name FROM complaint_replies r JOIN users u ON u.user_id = r.user_id WHERE r.complaint_id = :id ORDER BY r.created_at ASC");
$stmt->execute(['id' => $complaintId]);
$replies = $stmt->fetchAll();

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

<!-- Chat Section -->
<h2>Conversation & Responses</h2>
<div style="max-height: 300px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 1rem; background: #f8fafc; margin-bottom: 1rem;">
    <?php if (empty($replies)): ?>
        <p class="text-center text-slate-400 text-sm py-4">No replies yet. The admin will respond here.</p>
    <?php else: ?>
        <?php foreach ($replies as $reply): ?>
            <div class="flex mb-4" style="display: flex; margin-bottom: 1rem; justify-content: <?= $reply['is_admin'] ? 'flex-end' : 'flex-start' ?>;">
                <div style="max-width: 75%; padding: 0.75rem 1rem; border-radius: 0.5rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); background: <?= $reply['is_admin'] ? '#1e3a8a; color: white;' : 'white; color: #1e293b; border: 1px solid #e2e8f0;' ?>">
                    <p style="margin: 0; white-space: pre-wrap; font-size: 0.9em;"><?= e($reply['message']) ?></p>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.7em; text-align: right; color: <?= $reply['is_admin'] ? '#bfdbfe' : '#94a3b8' ?>;">
                        <?= e($reply['full_name']) ?> • <?= e(date('M j, g:i A', strtotime($reply['created_at']))) ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($complaint['status'] !== 'resolved'): ?>
<form method="POST" action="" style="display: flex; gap: 0.5rem; align-items: flex-end; margin-bottom: 2rem;">
    <?= csrfField() ?>
    <div style="flex: 1;">
        <textarea name="reply_message" rows="2" placeholder="Type your reply to the admin..." required style="resize: vertical;"></textarea>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top: 0; height: 42px;">Send</button>
</form>
<?php endif; ?>

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