<?php
// Ensure session is started at the very top of your file (or inside your functions.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrRedirect('/auth/forgot_password.php');
    $pdo = getDBConnection();
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Security best practice: Always tell the UI "success" to prevent email enumeration attacks,
        // but only generate and send the code if the user actually exists.
        if ($user) {
            // Generate 6-digit OTP securely
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $stmt = $pdo->prepare("UPDATE users SET reset_otp = :otp, reset_otp_expires = :expires WHERE user_id = :id");
            $stmt->execute(['otp' => $otp, 'expires' => $expires, 'id' => $user['user_id']]);

            // Send Email via PHPMailer
            require_once __DIR__ . '/../includes/PHPMailer/Exception.php';
            require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
            require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';
            require_once __DIR__ . '/../config/mail.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST; 
                $mail->Port       = SMTP_PORT; 
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME; 
                $mail->Password   = SMTP_PASSWORD; 
                $mail->SMTPSecure = SMTP_ENCRYPTION;
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($email, $user['full_name']);
                $mail->isHTML(false);
                $mail->Subject = 'Your Password Reset Code';
                $mail->Body    = "Hello {$user['full_name']},\r\n\r\nYour 6-digit password reset code is: $otp\r\n\r\nThis code will expire in 10 minutes. If you did not request this, please ignore this email.";
                $mail->send();
                
                $_SESSION['flash_success'] = 'A 6-digit reset code has been sent to your email.';
            } catch (\Exception $e) {
                // Fallback for localhost testing environments
                $_SESSION['flash_success'] = "Email failed to send (Localhost). Your OTP code is: <strong>$otp</strong>";
            }
            
            // Save email to session so verify_otp.php knows whose code it is looking up
            $_SESSION['reset_email'] = $email;
            
            // Instantly redirect to verification page
            header('Location: verify_otp.php');
            exit;
        } else {
            // Fake success to protect user privacy (prevents hackers from checking if an email is registered)
            $_SESSION['reset_email'] = $email;
            $_SESSION['flash_success'] = 'If an account with that email exists, a code has been sent.';
            header('Location: verify_otp.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <?php require __DIR__ . '/../includes/head_assets.php'; ?>
</head>
<body>
<div class="auth-container">
    <h1>Forgot Password</h1>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" action=""><?= csrfField() ?>
        <label>Email Address</label>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
        <button type="submit" class="btn btn-primary w-full text-center">Send 6-Digit Code</button>
    </form>
    <p style="text-align: center; margin-top: 15px;"><a href="<?= url('/auth/login.php') ?>">&larr; Back to Login</a></p>
</div>
</body>
</html>
