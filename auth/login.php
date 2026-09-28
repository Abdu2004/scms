<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect($_SESSION['role'] === 'student' ? '/student/dashboard.php' : '/admin/dashboard.php');
}

$errors = [];
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrRedirect('/auth/login.php');
    $pdo = getDBConnection();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'Your account has been suspended. Contact administration.';
        } else {
            // Regenerate session ID on login to prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['department_id'] = $user['department_id'];

            redirect($user['role'] === 'student' ? '/student/dashboard.php' : '/admin/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Complaint System</title>
    <?php require __DIR__ . '/../includes/head_assets.php'; ?>
</head>
<body>
    <div class="auth-container">
        <h1>Login</h1>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrfField() ?>
            <label>Email</label>
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>

            <label>Password</label>
            <input type="password" name="password" required>

               <button type="submit" class="btn btn-primary w-full text-center">Login</button>
    </form>
    <p style="text-align: center; margin-top: 15px;">
    <a href="<?= url('/auth/forgot_password.php') ?>" style="font-size: 0.9em;">Forgot Password?</a>
</p>
    <p style="text-align: center;">Don't have an account? <a href="<?= url('/auth/register.php') ?>">Register here</a></p>
</body>
</html>
