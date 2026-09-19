<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'super_admin']);

$pdo = getDBConnection();
$isSuperAdmin = $_SESSION['role'] === 'super_admin';
$deptId = $_SESSION['department_id'];

// Scope: super_admin sees everything, admin sees only their department's complaints
$scopeSql = $isSuperAdmin ? "" : "WHERE department_id = :dept_id";
$scopeParams = $isSuperAdmin ? [] : ['dept_id' => $deptId];

$stmt = $pdo->prepare("SELECT status, COUNT(*) AS total FROM complaints $scopeSql GROUP BY status");
$stmt->execute($scopeParams);
$counts = ['pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'rejected' => 0];
foreach ($stmt->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['total'];
}
$totalComplaints = array_sum($counts);

// Recent complaints in scope
$recentSql = "SELECT c.complaint_id, c.tracking_code, c.title, c.status, c.priority, c.created_at,
                     u.full_name AS student_name, cat.name AS category_name
              FROM complaints c
              JOIN users u ON u.user_id = c.student_id
              JOIN complaint_categories cat ON cat.category_id = c.category_id";
if (!$isSuperAdmin) $recentSql .= " WHERE c.department_id = :dept_id";
$recentSql .= " ORDER BY c.created_at DESC LIMIT 10";

$stmt = $pdo->prepare($recentSql);
$stmt->execute($scopeParams);
$recentComplaints = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Admin Dashboard</h1>
<p><?= $isSuperAdmin ? 'Viewing complaints across all departments.' : 'Viewing complaints for your department.' ?></p>

<div class="stats-grid">
    <div class="stat-card"><span class="stat-number"><?= $totalComplaints ?></span><span class="stat-label">Total</span></div>
    <div class="stat-card"><span class="stat-number"><?= $counts['pending'] ?></span><span class="stat-label">Pending</span></div>
    <div class="stat-card"><span class="stat-number"><?= $counts['in_progress'] ?></span><span class="stat-label">In Progress</span></div>
    <div class="stat-card"><span class="stat-number"><?= $counts['resolved'] ?></span><span class="stat-label">Resolved</span></div>
    <div class="stat-card"><span class="stat-number"><?= $counts['rejected'] ?></span><span class="stat-label">Rejected</span></div>
</div>

<div class="section-header">
    <h2>Recent Complaints</h2>
    <a href="<?= url('/admin/all_complaints.php') ?>" class="btn btn-primary">View All</a>
</div>

<?php if (empty($recentComplaints)): ?>
    <p>No complaints yet.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Tracking Code</th>
                <th>Student</th>
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
                    <td><?= e($c['student_name']) ?></td>
                    <td><?= e($c['title']) ?></td>
                    <td><?= e($c['category_name']) ?></td>
                    <td><span class="badge-status badge-<?= e($c['status']) ?>"><?= e(str_replace('_', ' ', $c['status'])) ?></span></td>
                    <td><?= e(ucfirst($c['priority'])) ?></td>
                    <td><?= e(date('M j, Y', strtotime($c['created_at']))) ?></td>
                    <td><a href="<?= url('/admin/complaint_detail.php') ?>?id=<?= (int) $c['complaint_id'] ?>">Manage</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
