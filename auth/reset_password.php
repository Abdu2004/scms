<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['reset_verified_user_id'])) {
    setFlash('error', 'Please verify your code first.');
    redirect('/auth/forgot_password.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrRedirect();
    $pdo = getDBConnection();
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($newPass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($newPass !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash, reset_otp = NULL, reset_otp_expires = NULL WHERE user_id = :id");
        $stmt->execute(['hash' => $hash, 'id' => $_SESSION['reset_verified_user_id']]);
        
        unset($_SESSION['reset_verified_user_id']);
        setFlash('success', 'Password reset successfully! Please log in.');
        redirect('/auth/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Set New Password</title><?php require __DIR__ . '/../includes/head_assets.php'; ?></head>
<body>
<div class="auth-container">
    <h1>Set New Password</h1>
    <?php if (!empty($errors)): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="POST" action=""><?= csrfField() ?>
        <label>New Password</label>
        <input type="password" name="new_password" required minlength="8">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required minlength="8">
        <button type="submit" class="btn btn-primary w-full text-center">Reset Password</button>
    </form>
</div>
</body>
</html>