<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'super_admin']);

$pdo = getDBConnection();
$isSuperAdmin = $_SESSION['role'] === 'super_admin';
$scopeSql = $isSuperAdmin ? "" : "WHERE c.department_id = :dept_id";
$scopeParams = $isSuperAdmin ? [] : ['dept_id' => $_SESSION['department_id']];

// Breakdown by category
$stmt = $pdo->prepare(
    "SELECT cat.name AS category_name, COUNT(*) AS total,
            SUM(c.status = 'resolved') AS resolved_count
     FROM complaints c
     JOIN complaint_categories cat ON cat.category_id = c.category_id
     $scopeSql
     GROUP BY cat.category_id, cat.name
     ORDER BY total DESC"
);
$stmt->execute($scopeParams);
$byCategory = $stmt->fetchAll();

// Breakdown by department (super admin only — an admin's complaints are all one department)
$byDepartment = [];
if ($isSuperAdmin) {
    $byDepartment = $pdo->query(
        "SELECT d.name AS department_name, COUNT(*) AS total,
                SUM(c.status = 'resolved') AS resolved_count
         FROM complaints c
         LEFT JOIN departments d ON d.department_id = c.department_id
         GROUP BY d.department_id, d.name
         ORDER BY total DESC"
    )->fetchAll();
}

// Average resolution time (in hours), for resolved complaints only
$avgSql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) AS avg_hours, COUNT(*) AS resolved_total
           FROM complaints c WHERE c.status = 'resolved' AND c.resolved_at IS NOT NULL";
if (!$isSuperAdmin) $avgSql .= " AND c.department_id = :dept_id";
$stmt = $pdo->prepare($avgSql);
$stmt->execute($scopeParams);
$resolutionStats = $stmt->fetch();
$avgHours = $resolutionStats['avg_hours'] !== null ? round((float) $resolutionStats['avg_hours'], 1) : null;

$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-header">
    <h1>Reports</h1>
    <a href="<?= url('/admin/export_complaints.php') ?>" class="btn btn-primary">&#8681; Export CSV</a>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
    <div class="stat-card">
        <span class="stat-number"><?= $avgHours !== null ? $avgHours . 'h' : '—' ?></span>
        <span class="stat-label">Avg. Resolution Time</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?= (int) ($resolutionStats['resolved_total'] ?? 0) ?></span>
        <span class="stat-label">Total Resolved</span>
    </div>
</div>

<h2>Complaints by Category</h2>
<table class="data-table">
    <thead><tr><th>Category</th><th>Total</th><th>Resolved</th><th>Resolution Rate</th></tr></thead>
    <tbody>
        <?php if (empty($byCategory)): ?>
            <tr><td colspan="4">No data yet.</td></tr>
        <?php else: foreach ($byCategory as $row): ?>
            <tr>
                <td><?= e($row['category_name']) ?></td>
                <td><?= (int) $row['total'] ?></td>
                <td><?= (int) $row['resolved_count'] ?></td>
                <td><?= $row['total'] > 0 ? round(($row['resolved_count'] / $row['total']) * 100) . '%' : '—' ?></td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<?php if ($isSuperAdmin): ?>
<h2>Complaints by Department</h2>
<table class="data-table">
    <thead><tr><th>Department</th><th>Total</th><th>Resolved</th><th>Resolution Rate</th></tr></thead>
    <tbody>
        <?php if (empty($byDepartment)): ?>
            <tr><td colspan="4">No data yet.</td></tr>
        <?php else: foreach ($byDepartment as $row): ?>
            <tr>
                <td><?= e($row['department_name'] ?? 'Unassigned') ?></td>
                <td><?= (int) $row['total'] ?></td>
                <td><?= (int) $row['resolved_count'] ?></td>
                <td><?= $row['total'] > 0 ? round(($row['resolved_count'] / $row['total']) * 100) . '%' : '—' ?></td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
