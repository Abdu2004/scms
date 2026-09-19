<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin']);

$pdo = getDBConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrRedirect();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if ($name === '') {
            $errors[] = 'Department name is required.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO departments (name, description) VALUES (:name, :desc)");
            $stmt->execute(['name' => $name, 'desc' => $desc !== '' ? $desc : null]);
            setFlash('success', 'Department added.');
            redirect('/admin/manage_departments.php');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['department_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM departments WHERE department_id = :id");
            $stmt->execute(['id' => $id]);
            setFlash('success', 'Department deleted.');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                setFlash('error', 'Cannot delete this department because it is referenced by existing users or complaints. Please reassign them first.');
            } else {
                setFlash('error', 'An error occurred while deleting the department.');
            }
        }
        redirect('/admin/manage_departments.php');
    } elseif ($action === 'edit') {
        $id   = (int) ($_POST['department_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if ($name === '') {
            $errors[] = 'Department name is required.';
        } else {
            $stmt = $pdo->prepare("UPDATE departments SET name = :name, description = :desc WHERE department_id = :id");
            $stmt->execute(['name' => $name, 'desc' => $desc !== '' ? $desc : null, 'id' => $id]);
            setFlash('success', 'Department updated.');
            redirect('/admin/manage_departments.php');
        }
    }
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

$pageTitle = 'Manage Departments';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Manage Departments</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="" class="form-card">
    <input type="hidden" name="action" value="add">
    <?= csrfField() ?>
    <h2>Add Department</h2>
    <label>Name</label>
    <input type="text" name="name" required>
    <label>Description</label>
    <input type="text" name="description">
    <button type="submit" class="btn btn-primary">Add</button>
</form>

<table class="data-table">
    <thead><tr><th>Name</th><th>Description</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($departments as $d): ?>
            <tr>
                <td colspan="3">
                <form method="POST" action="" class="inline-row-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="department_id" value="<?= (int) $d['department_id'] ?>">
                    <input type="text" name="name" value="<?= e($d['name']) ?>">
                    <input type="text" name="description" value="<?= e($d['description'] ?? '') ?>">
                    <button type="submit" name="action" value="edit" class="btn-link">Save</button>
                    <button type="submit" name="action" value="delete" class="btn-link btn-danger" onclick="return confirm('Delete this department?');">Delete</button>
                </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
