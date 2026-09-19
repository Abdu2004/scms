<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['student']);

$pdo = getDBConnection();
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT c.complaint_id, c.tracking_code, c.title, c.status, c.priority, c.created_at, cat.name AS category_name
        FROM complaints c
        JOIN complaint_categories cat ON cat.category_id = c.category_id
        WHERE c.student_id = :sid";
$params = ['sid' => $_SESSION['user_id']];

if (in_array($statusFilter, ['pending', 'in_progress', 'resolved', 'rejected'], true)) {
    $sql .= " AND c.status = :status";
    $params['status'] = $statusFilter;
}
$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$pageTitle = 'My Complaints';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>My Complaints</h1>

<div class="filter-bar">
    <a href="?" class="<?= $statusFilter === '' ? 'active' : '' ?>">All</a>
    <a href="?status=pending" class="<?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
    <a href="?status=in_progress" class="<?= $statusFilter === 'in_progress' ? 'active' : '' ?>">In Progress</a>
    <a href="?status=resolved" class="<?= $statusFilter === 'resolved' ? 'active' : '' ?>">Resolved</a>
    <a href="?status=rejected" class="<?= $statusFilter === 'rejected' ? 'active' : '' ?>">Rejected</a>
</div>

<?php if (empty($complaints)): ?>
    <p>No complaints found for this filter.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Tracking Code</th>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Date Submitted</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($complaints as $c): ?>
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
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
