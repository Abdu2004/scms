<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure they came from the forgot password page
if (empty($_SESSION['reset_email'])) {
    setFlash('error', 'Please request a reset code first.');
    redirect('/auth/forgot_password.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrRedirect();
    $pdo = getDBConnection();
    
    // 1. Clean the input strictly
    $otp_input = trim($_POST['otp'] ?? '');
    $email = trim($_SESSION['reset_email']);

    if (strlen($otp_input) !== 6 || !is_numeric($otp_input)) {
        $errors[] = 'Please enter a valid 6-digit code.';
    } else {
        // 2. Fetch the user by email FIRST
        $stmt = $pdo->prepare("SELECT user_id, reset_otp, reset_otp_expires FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // 3. Clean the database values to prevent hidden space issues
            $db_otp = trim((string)$user['reset_otp']);
            $db_expires = $user['reset_otp_expires'];

            // 4. Check if OTP matches exactly
            if ($db_otp === $otp_input) {
                
                // 5. Check expiration using PHP's reliable time functions
                if ($db_expires && strtotime($db_expires) > time()) {
                    // SUCCESS! Code is valid and not expired.
                    $_SESSION['reset_verified_user_id'] = $user['user_id'];
                    unset($_SESSION['reset_email']); // Clean up session
                    
                    setFlash('success', 'Code verified successfully!');
                    redirect('/auth/reset_password.php');
                } else {
                    $errors[] = 'This code has expired. Please request a new one.';
                }
            } else {
                $errors[] = 'Invalid code. Please check the number and try again.';
            }
        } else {
            $errors[] = 'Invalid code or email not found.';
        }
    }
}
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify Code</title>
<?php require __DIR__ . '/../includes/head_assets.php'; ?>
</head>
<body>
<div class="auth-container">
    <h1>Enter 6-Digit Code</h1>
    <p style="color: #64748b; font-size: 0.9em; margin-bottom: 20px;">
        We sent a code to <strong><?= e($_SESSION['reset_email']) ?></strong>
    </p>
    
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?= csrfField() ?>
        <label>6-Digit Code</label>
        <input type="text" name="otp" maxlength="6" pattern="[0-9]{6}" required 
               style="letter-spacing: 5px; text-align: center; font-size: 1.2em;" 
               value="<?= e($_POST['otp'] ?? '') ?>">
        <button type="submit" class="btn btn-primary w-full text-center">Verify Code</button>
    </form>
    <p style="text-align: center; margin-top: 15px;">
        <a href="<?= url('/auth/forgot_password.php') ?>">Didn't get the code? Request a new one</a>
    </p>
</div>
</body>
</html>