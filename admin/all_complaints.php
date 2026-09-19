<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'super_admin']);

$pdo = getDBConnection();
$isSuperAdmin = $_SESSION['role'] === 'super_admin';

$statusFilter   = $_GET['status'] ?? '';
$categoryFilter = (int) ($_GET['category_id'] ?? 0);
$search         = trim($_GET['q'] ?? '');

$sql = "SELECT c.complaint_id, c.tracking_code, c.title, c.status, c.priority, c.created_at,
               u.full_name AS student_name, cat.name AS category_name, d.name AS department_name,
               a.full_name AS assigned_admin_name
        FROM complaints c
        JOIN users u ON u.user_id = c.student_id
        JOIN complaint_categories cat ON cat.category_id = c.category_id
        LEFT JOIN departments d ON d.department_id = c.department_id
        LEFT JOIN users a ON a.user_id = c.assigned_admin_id
        WHERE 1=1";
$params = [];

if (!$isSuperAdmin) {
    $sql .= " AND c.department_id = :dept_id";
    $params['dept_id'] = $_SESSION['department_id'];
}
if (in_array($statusFilter, ['pending', 'in_progress', 'resolved', 'rejected'], true)) {
    $sql .= " AND c.status = :status";
    $params['status'] = $statusFilter;
}
if ($categoryFilter > 0) {
    $sql .= " AND c.category_id = :category_id";
    $params['category_id'] = $categoryFilter;
}
if ($search !== '') {
    $sql .= " AND (c.title LIKE :search OR c.tracking_code LIKE :search OR u.full_name LIKE :search)";
    $params['search'] = "%$search%";
}
$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$categories = $pdo->query("SELECT category_id, name FROM complaint_categories ORDER BY name")->fetchAll();

$pageTitle = 'All Complaints';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>All Complaints</h1>

<form method="GET" action="" class="filter-form">
    <input type="text" name="q" placeholder="Search title, code, or student" value="<?= e($search) ?>">

    <select name="status">
        <option value="">All Statuses</option>
        <?php foreach (['pending', 'in_progress', 'resolved', 'rejected'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($s))) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="category_id">
        <option value="0">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['category_id'] ?>" <?= $categoryFilter === (int) $cat['category_id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="<?= url('/admin/all_complaints.php') ?>">Reset</a>
</form>

<?php if (empty($complaints)): ?>
    <p>No complaints match your filters.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Tracking Code</th>
                <th>Student</th>
                <th>Title</th>
                <th>Category</th>
                <th>Department</th>
                <th>Assigned To</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($complaints as $c): ?>
                <tr>
                    <td><?= e($c['tracking_code']) ?></td>
                    <td><?= e($c['student_name']) ?></td>
                    <td><?= e($c['title']) ?></td>
                    <td><?= e($c['category_name']) ?></td>
                    <td><?= e($c['department_name'] ?? '—') ?></td>
                    <td><?= e($c['assigned_admin_name'] ?? 'Unassigned') ?></td>
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
