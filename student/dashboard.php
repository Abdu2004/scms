<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['student']);

$pdo = getDBConnection();
$studentId = $_SESSION['user_id'];

// Summary counts
$stmt = $pdo->prepare(
    "SELECT status, COUNT(*) AS total
     FROM complaints
     WHERE student_id = :sid
     GROUP BY status"
);
$stmt->execute(['sid' => $studentId]);
$counts = ['pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'rejected' => 0];
foreach ($stmt->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['total'];
}
$totalComplaints = array_sum($counts);

// Recent complaints
$stmt = $pdo->prepare(
    "SELECT c.complaint_id, c.tracking_code, c.title, c.status, c.priority, c.created_at, cat.name AS category_name
     FROM complaints c
     JOIN complaint_categories cat ON cat.category_id = c.category_id
     WHERE c.student_id = :sid
     ORDER BY c.created_at DESC
     LIMIT 5"
);
$stmt->execute(['sid' => $studentId]);
$recentComplaints = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Welcome, <?= e($_SESSION['full_name']) ?></h1>

<div class="stats-grid">
    <div class="stat-card"><span class="stat-number"><?= $totalComplaints ?></span><span class="stat-label">Total</span></div>
    <div class="stat-card"><span class="stat-number"><?= $counts['pending'] ?></span><span class="stat-label">Pending</span></div>
    <div class="stat-card"><span class="stat-number"><?= $counts['in_progress'] ?></span><span class="stat-label">In Progress</span></div>
    <div class="stat-card"><span class="stat-number"><?= $counts['resolved'] ?></span><span class="stat-label">Resolved</span></div>
    <div class="stat-card"><span class="stat-number"><?= $counts['rejected'] ?></span><span class="stat-label">Rejected</span></div>
</div>

<div class="section-header">
    <h2>Recent Complaints</h2>
    <a href="<?= url('/student/submit_complaint.php') ?>" class="btn btn-primary">+ New Complaint</a>
</div>

<?php if (empty($recentComplaints)): ?>
    <p>You haven't submitted any complaints yet.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Tracking Code</th>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentComplaints as $c): ?>
                <tr>
                    <td><?= e($c['tracking_code']) ?></td>
                    <td><?= e($c['title']) ?></td>
                    <td><?= e($c['category_name']) ?></td>
                    <td><span class="badge-status badge-<?= e($c['status']) ?>"><?= e(str_replace('_', ' ', $c['status'])) ?></span></td>
                    <td><?= e(ucfirst($c['priority'])) ?></td>
                    <td><?= e(date('M j, Y', strtotime($c['created_at']))) ?></td>
                    <td><a href="<?= url('/student/complaint_detail.php') ?>?id=<?= (int) $c['complaint_id'] ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p><a href="<?= url('/student/my_complaints.php') ?>">View all my complaints &rarr;</a></p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
