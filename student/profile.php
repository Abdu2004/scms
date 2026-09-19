<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['student']);

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$errors = [];

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrRedirect();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $faculty  = trim($_POST['faculty'] ?? '');
        $level    = trim($_POST['level'] ?? '');

        if ($fullName === '') $errors[] = 'Full name is required.';

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "UPDATE users SET full_name = :full_name, phone = :phone, faculty = :faculty, level = :level WHERE user_id = :id"
            );
            $stmt->execute([
                'full_name' => $fullName,
                'phone'     => $phone !== '' ? $phone : null,
                'faculty'   => $faculty !== '' ? $faculty : null,
                'level'     => $level !== '' ? $level : null,
                'id'        => $userId,
            ]);
            $_SESSION['full_name'] = $fullName;
            setFlash('success', 'Profile updated successfully.');
            redirect('/student/profile.php');
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($newPass !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :id");
            $stmt->execute(['hash' => password_hash($newPass, PASSWORD_DEFAULT), 'id' => $userId]);
            // Invalidate the old session ID to prevent session fixation
            session_regenerate_id(true);
            setFlash('success', 'Password changed successfully.');
            redirect('/student/profile.php');
        }
    }
}

$pageTitle = 'Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>My Profile</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="two-col">
    <form method="POST" action="" class="form-card">
        <?= csrfField() ?>
        <h2>Profile Information</h2>
        <input type="hidden" name="action" value="update_profile">

        <label>Full Name</label>
        <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>

        <label>Email</label>
        <input type="email" value="<?= e($user['email']) ?>" disabled>

        <label>Registration Number</label>
        <input type="text" value="<?= e($user['reg_number']) ?>" disabled>

        <label>Faculty</label>
        <input type="text" name="faculty" value="<?= e($user['faculty'] ?? '') ?>">

        <label>Level</label>
        <input type="text" name="level" value="<?= e($user['level'] ?? '') ?>">

        <label>Phone</label>
        <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>">

        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>

    <form method="POST" action="" class="form-card">
        <?= csrfField() ?>
        <h2>Change Password</h2>
        <input type="hidden" name="action" value="change_password">

        <label>Current Password</label>
        <input type="password" name="current_password" required>

        <label>New Password</label>
        <input type="password" name="new_password" required minlength="8">

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required minlength="8">

        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
