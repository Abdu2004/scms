<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin']);

$pdo = getDBConnection();
$errors = [];
$departments = $pdo->query("SELECT department_id, name FROM departments ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrRedirect();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_admin') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $deptId   = (int) ($_POST['department_id'] ?? 0);

        if ($fullName === '') $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO users (role, full_name, email, password_hash, department_id)
                     VALUES ('admin', :full_name, :email, :hash, :dept_id)"
                );
                $stmt->execute([
                    'full_name' => $fullName,
                    'email'     => $email,
                    'hash'      => password_hash($password, PASSWORD_DEFAULT),
                    'dept_id'   => $deptId > 0 ? $deptId : null,
                ]);
                setFlash('success', 'Admin account created.');
                redirect('/admin/manage_users.php');
            }
        }
    } elseif ($action === 'toggle_status') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        // Prevent self-suspension
        if ($userId === (int) $_SESSION['user_id']) {
            $errors[] = 'You cannot suspend your own account.';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE users SET status = IF(status = 'active', 'suspended', 'active') WHERE user_id = :id"
            );
            $stmt->execute(['id' => $userId]);
            setFlash('success', 'User status updated.');
            redirect('/admin/manage_users.php');
        }
    }
}

$students = $pdo->query(
    "SELECT user_id, full_name, email, reg_number, faculty, level, status, created_at
     FROM users WHERE role = 'student' ORDER BY created_at DESC"
)->fetchAll();

$admins = $pdo->query(
    "SELECT u.user_id, u.full_name, u.email, u.role, u.status, u.created_at, d.name AS department_name
     FROM users u LEFT JOIN departments d ON d.department_id = u.department_id
     WHERE u.role IN ('admin', 'super_admin') ORDER BY u.created_at DESC"
)->fetchAll();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Manage Users</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="" class="form-card">
    <input type="hidden" name="action" value="add_admin">
    <?= csrfField() ?>
    <h2>Add Department Admin</h2>
    <label>Full Name</label>
    <input type="text" name="full_name" required>
    <label>Email</label>
    <input type="email" name="email" required>
    <label>Department</label>
    <select name="department_id">
        <option value="0">— None (view only) —</option>
        <?php foreach ($departments as $d): ?>
            <option value="<?= (int) $d['department_id'] ?>"><?= e($d['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <label>Temporary Password</label>
    <input type="password" name="password" required minlength="8">
    <button type="submit" class="btn btn-primary">Create Admin</button>
</form>

<h2>Admins</h2>
<table class="data-table">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($admins as $a): ?>
            <tr>
                <td><?= e($a['full_name']) ?></td>
                <td><?= e($a['email']) ?></td>
                <td><?= e($a['role']) ?></td>
                <td><?= e($a['department_name'] ?? '—') ?></td>
                <td><?= e($a['status']) ?></td>
                <td>
                    <?php if ($a['role'] !== 'super_admin'): ?>
                    <form method="POST" action="" style="display:inline">
                        <input type="hidden" name="action" value="toggle_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $a['user_id'] ?>">
                        <button type="submit" class="btn-link"><?= $a['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h2>Students</h2>
<table class="data-table">
    <thead><tr><th>Name</th><th>Email</th><th>Reg No.</th><th>Faculty</th><th>Level</th><th>Status</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($students as $s): ?>
            <tr>
                <td><?= e($s['full_name']) ?></td>
                <td><?= e($s['email']) ?></td>
                <td><?= e($s['reg_number']) ?></td>
                <td><?= e($s['faculty'] ?? '—') ?></td>
                <td><?= e($s['level'] ?? '—') ?></td>
                <td><?= e($s['status']) ?></td>
                <td>
                    <form method="POST" action="" style="display:inline">
                        <input type="hidden" name="action" value="toggle_status">
                        <?= csrfField() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $s['user_id'] ?>">
                        <button type="submit" class="btn-link"><?= $s['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
