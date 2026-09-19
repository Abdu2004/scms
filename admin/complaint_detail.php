<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'super_admin']);

$pdo = getDBConnection();
$isSuperAdmin = $_SESSION['role'] === 'super_admin';
$complaintId = (int) ($_GET['id'] ?? 0);
$errors = [];

function loadComplaint(PDO $pdo, int $id, bool $isSuperAdmin, ?int $deptId = null): ?array
{
    $sql = "SELECT c.*, u.full_name AS student_name, u.email AS student_email, u.reg_number,
                   cat.name AS category_name, d.name AS department_name
            FROM complaints c
            JOIN users u ON u.user_id = c.student_id
            JOIN complaint_categories cat ON cat.category_id = c.category_id
            LEFT JOIN departments d ON d.department_id = c.department_id
            WHERE c.complaint_id = :id";
    $params = ['id' => $id];
    if (!$isSuperAdmin) {
        $sql .= " AND c.department_id = :dept_id";
        $params['dept_id'] = $deptId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

$complaint = loadComplaint($pdo, $complaintId, $isSuperAdmin, $_SESSION['department_id']);
if (!$complaint) {
    setFlash('error', 'Complaint not found or not in your scope.');
    redirect('/admin/all_complaints.php');
}

// Admins eligible for assignment: same department, or all admins for super_admin
$adminSql = "SELECT user_id, full_name FROM users WHERE role IN ('admin','super_admin')";
if (!$isSuperAdmin) $adminSql .= " AND department_id = :dept_id";
$adminSql .= " ORDER BY full_name";
$adminStmt = $pdo->prepare($adminSql);
$adminStmt->execute($isSuperAdmin ? [] : ['dept_id' => $_SESSION['department_id']]);
$eligibleAdmins = $adminStmt->fetchAll();

$departments = $pdo->query("SELECT department_id, name FROM departments ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrRedirect();
    $newStatus     = $_POST['status'] ?? $complaint['status'];
    $assignedAdmin = (int) ($_POST['assigned_admin_id'] ?? 0);
    $departmentId  = (int) ($_POST['department_id'] ?? 0);
    $resolutionNote = trim($_POST['resolution_note'] ?? '');
    $remarks        = trim($_POST['remarks'] ?? '');

    if (!in_array($newStatus, ['pending', 'in_progress', 'resolved', 'rejected'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    if (empty($errors)) {
        $oldStatus = $complaint['status'];

        $stmt = $pdo->prepare(
            "UPDATE complaints
             SET status = :status,
                 assigned_admin_id = :assigned_admin_id,
                 department_id = :department_id,
                 resolution_note = COALESCE(NULLIF(:resolution_note, ''), resolution_note),
                 resolved_at = CASE WHEN :status2 = 'resolved' THEN NOW() ELSE NULL END
             WHERE complaint_id = :id"
        );
        $stmt->execute([
            'status'           => $newStatus,
            'status2'          => $newStatus,
            'assigned_admin_id' => $assignedAdmin > 0 ? $assignedAdmin : null,
            'department_id'    => $departmentId > 0 ? $departmentId : null,
            'resolution_note'  => $resolutionNote,
            'id'               => $complaintId,
        ]);

        if ($oldStatus !== $newStatus) {
            logStatusChange($pdo, $complaintId, $oldStatus, $newStatus, $_SESSION['user_id'], $remarks ?: null);

            // Notify the student of the status change
            $studentMsg = "Your complaint {$complaint['tracking_code']} status changed to " . str_replace('_', ' ', $newStatus) . ".";
            notifyUser($pdo, (int) $complaint['student_id'], $studentMsg, $complaintId, 'email');
        }

        setFlash('success', 'Complaint updated successfully.');
        redirect('/admin/complaint_detail.php?id=' . $complaintId);
    }

    $complaint = loadComplaint($pdo, $complaintId, $isSuperAdmin, $_SESSION['department_id']);
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

$pageTitle = 'Manage Complaint';
require_once __DIR__ . '/../includes/header.php';
?>

<h1><?= e($complaint['title']) ?></h1>
<p class="tracking-code">Tracking Code: <strong><?= e($complaint['tracking_code']) ?></strong></p>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="two-col">
    <div class="detail-card">
        <p><strong>Student:</strong> <?= e($complaint['student_name']) ?> (<?= e($complaint['reg_number']) ?>)</p>
        <p><strong>Email:</strong> <?= e($complaint['student_email']) ?></p>
        <p><strong>Category:</strong> <?= e($complaint['category_name']) ?></p>
        <p><strong>Priority:</strong> <?= e(ucfirst($complaint['priority'])) ?></p>
        <p><strong>Submitted:</strong> <?= e(date('M j, Y g:i A', strtotime($complaint['created_at']))) ?></p>
        <p><strong>Description:</strong></p>
        <p><?= nl2br(e($complaint['description'])) ?></p>

        <?php if (!empty($attachments)): ?>
            <p><strong>Attachments:</strong></p>
            <ul>
                <?php foreach ($attachments as $a): ?>
                    <li><a href="<?= url('/' . $a['file_path']) ?>" target="_blank"><?= e($a['original_name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <form method="POST" action="" class="form-card">
        <?= csrfField() ?>
        <h2>Manage Complaint</h2>

        <label>Status</label>
        <select name="status">
            <?php foreach (['pending', 'in_progress', 'resolved', 'rejected'] as $s): ?>
                <option value="<?= $s ?>" <?= $complaint['status'] === $s ? 'selected' : '' ?>>
                    <?= e(str_replace('_', ' ', ucfirst($s))) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($isSuperAdmin): ?>
        <label>Department</label>
        <select name="department_id">
            <option value="0">— Unassigned —</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= (int) $d['department_id'] ?>" <?= (int) $complaint['department_id'] === (int) $d['department_id'] ? 'selected' : '' ?>>
                    <?= e($d['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php else: ?>
            <input type="hidden" name="department_id" value="<?= (int) $complaint['department_id'] ?>">
        <?php endif; ?>

        <label>Assign to Admin</label>
        <select name="assigned_admin_id">
            <option value="0">— Unassigned —</option>
            <?php foreach ($eligibleAdmins as $a): ?>
                <option value="<?= (int) $a['user_id'] ?>" <?= (int) $complaint['assigned_admin_id'] === (int) $a['user_id'] ? 'selected' : '' ?>>
                    <?= e($a['full_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Resolution Note (visible to student)</label>
        <textarea name="resolution_note" rows="4"><?= e($complaint['resolution_note'] ?? '') ?></textarea>

        <label>Internal Remarks (for history log)</label>
        <textarea name="remarks" rows="2" placeholder="Optional note about this update"></textarea>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<h2>Status History</h2>
<ul class="timeline">
    <?php foreach ($history as $h): ?>
        <li>
            <strong><?= e(str_replace('_', ' ', $h['new_status'])) ?></strong>
            by <?= e($h['changed_by_name'] ?? 'System') ?>
            — <?= e(date('M j, Y g:i A', strtotime($h['changed_at']))) ?>
            <?php if ($h['remarks']): ?><br><em><?= e($h['remarks']) ?></em><?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>

<p><a href="<?= url('/admin/all_complaints.php') ?>">&larr; Back to All Complaints</a></p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
