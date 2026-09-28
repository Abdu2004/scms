<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrRedirect();
    $pdo = getDBConnection();
    $email = trim($_POST['email'] ?? '');
    $otp   = trim($_POST['otp'] ?? '');

    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email AND reset_otp = :otp AND reset_otp_expires > NOW() LIMIT 1");
    $stmt->execute(['email' => $email, 'otp' => $otp]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['reset_verified_user_id'] = $user['user_id'];
        redirect('/auth/reset_password.php');
    } else {
        $errors[] = 'Invalid code or code has expired.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Verify Code</title><?php require __DIR__ . '/../includes/head_assets.php'; ?></head>
<body>
<div class="auth-container">
    <h1>Enter 6-Digit Code</h1>
    <?php if (!empty($errors)): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="POST" action=""><?= csrfField() ?>
        <label>Email Address</label>
        <input type="email" name="email" required>
        <label>6-Digit Code</label>
        <input type="text" name="otp" maxlength="6" pattern="[0-9]{6}" required style="letter-spacing: 5px; text-align: center; font-size: 1.2em;">
        <button type="submit" class="btn btn-primary w-full text-center">Verify Code</button>
    </form>
</div>
</body>
</html>