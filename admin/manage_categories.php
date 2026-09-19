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
    $name   = trim($_POST['name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $deptId = (int) ($_POST['default_department_id'] ?? 0);

    if ($action === 'add') {
        if ($name === '') {
            $errors[] = 'Category name is required.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO complaint_categories (name, description, default_department_id) VALUES (:name, :desc, :dept)"
            );
            $stmt->execute(['name' => $name, 'desc' => $desc !== '' ? $desc : null, 'dept' => $deptId > 0 ? $deptId : null]);
            setFlash('success', 'Category added.');
            redirect('/admin/manage_categories.php');
        }
    } elseif ($action === 'edit') {
        $id = (int) ($_POST['category_id'] ?? 0);
        if ($name === '') {
            $errors[] = 'Category name is required.';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE complaint_categories SET name = :name, description = :desc, default_department_id = :dept WHERE category_id = :id"
            );
            $stmt->execute(['name' => $name, 'desc' => $desc !== '' ? $desc : null, 'dept' => $deptId > 0 ? $deptId : null, 'id' => $id]);
            setFlash('success', 'Category updated.');
            redirect('/admin/manage_categories.php');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['category_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM complaint_categories WHERE category_id = :id");
            $stmt->execute(['id' => $id]);
            setFlash('success', 'Category deleted.');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                setFlash('error', 'Cannot delete this category because it is referenced by existing complaints. Please reassign those complaints first.');
            } else {
                setFlash('error', 'An error occurred while deleting the category.');
            }
        }
        redirect('/admin/manage_categories.php');
    }
}

$categories = $pdo->query(
    "SELECT cat.*, d.name AS department_name
     FROM complaint_categories cat
     LEFT JOIN departments d ON d.department_id = cat.default_department_id
     ORDER BY cat.name"
)->fetchAll();

$pageTitle = 'Manage Categories';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Manage Complaint Categories</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="" class="form-card">
    <input type="hidden" name="action" value="add">
    <?= csrfField() ?>
    <h2>Add Category</h2>
    <label>Name</label>
    <input type="text" name="name" required>
    <label>Description</label>
    <input type="text" name="description">
    <label>Default Department</label>
    <select name="default_department_id">
        <option value="0">— None —</option>
        <?php foreach ($departments as $d): ?>
            <option value="<?= (int) $d['department_id'] ?>"><?= e($d['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Add</button>
</form>

<table class="data-table">
    <thead><tr><th>Name</th><th>Description</th><th>Default Department</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($categories as $c): ?>
            <tr>
                <td colspan="4">
                <form method="POST" action="" class="inline-row-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="category_id" value="<?= (int) $c['category_id'] ?>">
                    <input type="text" name="name" value="<?= e($c['name']) ?>">
                    <input type="text" name="description" value="<?= e($c['description'] ?? '') ?>">
                    <select name="default_department_id">
                        <option value="0">— None —</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= (int) $d['department_id'] ?>" <?= (int) $c['default_department_id'] === (int) $d['department_id'] ? 'selected' : '' ?>>
                                <?= e($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="action" value="edit" class="btn-link">Save</button>
                    <button type="submit" name="action" value="delete" class="btn-link btn-danger" onclick="return confirm('Delete this category?');">Delete</button>
                </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
