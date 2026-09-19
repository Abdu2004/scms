<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'super_admin']);

$pdo = getDBConnection();
$isSuperAdmin = $_SESSION['role'] === 'super_admin';

$sql = "SELECT c.tracking_code, u.full_name AS student_name, u.reg_number, cat.name AS category_name,
               d.name AS department_name, c.title, c.status, c.priority, c.created_at, c.resolved_at
        FROM complaints c
        JOIN users u ON u.user_id = c.student_id
        JOIN complaint_categories cat ON cat.category_id = c.category_id
        LEFT JOIN departments d ON d.department_id = c.department_id";
$params = [];
if (!$isSuperAdmin) {
    $sql .= " WHERE c.department_id = :dept_id";
    $params['dept_id'] = $_SESSION['department_id'];
}
$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="complaints_export_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Tracking Code', 'Student', 'Reg. Number', 'Category', 'Department', 'Title', 'Status', 'Priority', 'Submitted', 'Resolved']);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['tracking_code'],
        $r['student_name'],
        $r['reg_number'],
        $r['category_name'],
        $r['department_name'] ?? 'Unassigned',
        $r['title'],
        str_replace('_', ' ', $r['status']),
        $r['priority'],
        $r['created_at'],
        $r['resolved_at'] ?? '',
    ]);
}
fclose($out);
exit;
