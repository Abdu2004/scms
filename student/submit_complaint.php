<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['student']);

$pdo = getDBConnection();
$errors = [];

// Load categories for the dropdown
$categories = $pdo->query("SELECT category_id, name, default_department_id FROM complaint_categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrRedirect();
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $title      = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority   = $_POST['priority'] ?? 'medium';

    if ($categoryId <= 0) $errors[] = 'Please select a category.';
    if ($title === '') $errors[] = 'Title is required.';
    if ($description === '') $errors[] = 'Description is required.';
    if (!in_array($priority, ['low', 'medium', 'high'], true)) $priority = 'medium';

    if (empty($errors)) {
        // Look up the category's default department for routing
        $stmt = $pdo->prepare("SELECT default_department_id FROM complaint_categories WHERE category_id = :cid");
        $stmt->execute(['cid' => $categoryId]);
        $cat = $stmt->fetch();
        $departmentId = $cat ? $cat['default_department_id'] : null;

        $trackingCode = generateTrackingCode($pdo);

        $stmt = $pdo->prepare(
            "INSERT INTO complaints (tracking_code, student_id, category_id, department_id, title, description, priority, status)
             VALUES (:tracking_code, :student_id, :category_id, :department_id, :title, :description, :priority, 'pending')"
        );
        $stmt->execute([
            'tracking_code' => $trackingCode,
            'student_id'    => $_SESSION['user_id'],
            'category_id'   => $categoryId,
            'department_id' => $departmentId,
            'title'         => $title,
            'description'   => $description,
            'priority'      => $priority,
        ]);

        $complaintId = (int) $pdo->lastInsertId();
        logStatusChange($pdo, $complaintId, null, 'pending', $_SESSION['user_id'], 'Complaint submitted by student.');

        // Handle optional file attachment
        if (!empty($_FILES['attachment']['name'])) {
            $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
            $file = $_FILES['attachment'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Attachment upload failed (error code ' . $file['error'] . '). Please try again.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Attachment must be 5MB or smaller.';
            } else {
                // Validate MIME type server-side using finfo (not the client-provided $file['type'])
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $detectedMime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($detectedMime, $allowedMimes, true)) {
                    $errors[] = 'Attachment must be a JPEG, PNG, or PDF file.';
                } else {
                    $uploadDir = __DIR__ . '/../uploads/complaints/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
                    $destPath = $uploadDir . $safeName;

                    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                        $errors[] = 'Failed to save the uploaded file. Please try again.';
                    } else {
                        $stmt = $pdo->prepare(
                            "INSERT INTO complaint_attachments (complaint_id, file_path, original_name)
                             VALUES (:complaint_id, :file_path, :original_name)"
                        );
                        $stmt->execute([
                            'complaint_id'  => $complaintId,
                            'file_path'     => 'uploads/complaints/' . $safeName,
                            'original_name' => $file['name'],
                        ]);
                    }
                }
            }
        }

        // Notify assigned department admins (if any) that a new complaint came in
        if ($departmentId) {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE department_id = :did AND role = 'admin'");
            $stmt->execute(['did' => $departmentId]);
            foreach ($stmt->fetchAll() as $admin) {
                notifyUser($pdo, $admin['user_id'], "New complaint submitted: $trackingCode", $complaintId);
            }
        }

        setFlash('success', "Complaint submitted successfully. Your tracking code is $trackingCode.");
        redirect('/student/my_complaints.php');
    }
}

$pageTitle = 'Submit Complaint';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Submit a Complaint</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" class="form-card">
    <?= csrfField() ?>
    <label>Category</label>
    <select name="category_id" required>
        <option value="">-- Select Category --</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['category_id'] ?>" <?= (($_POST['category_id'] ?? '') == $cat['category_id']) ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Title</label>
    <input type="text" name="title" value="<?= e($_POST['title'] ?? '') ?>" maxlength="200" required>

    <label>Description</label>
    <textarea name="description" rows="6" required><?= e($_POST['description'] ?? '') ?></textarea>

    <label>Priority</label>
    <select name="priority">
        <option value="low">Low</option>
        <option value="medium" selected>Medium</option>
        <option value="high">High</option>
    </select>

    <label>Attachment (optional — image or PDF, max 5MB)</label>
    <input type="file" name="attachment" accept="image/jpeg,image/png,application/pdf">

    <button type="submit" class="btn btn-primary">Submit Complaint</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
