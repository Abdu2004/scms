<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('/student/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrRedirect('/auth/register.php');
    $pdo = getDBConnection();

    $fullName  = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $regNumber = trim($_POST['reg_number'] ?? '');
    $faculty   = trim($_POST['faculty'] ?? '');
    $level     = trim($_POST['level'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // --- Validation ---
    if ($fullName === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($regNumber === '') $errors[] = 'Registration number is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Check for existing email or reg number
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email OR reg_number = :reg_number");
        $stmt->execute(['email' => $email, 'reg_number' => $regNumber]);

        if ($stmt->fetch()) {
            $errors[] = 'An account with this email or registration number already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO users (role, full_name, email, password_hash, phone, reg_number, faculty, level)
                 VALUES ('student', :full_name, :email, :password_hash, :phone, :reg_number, :faculty, :level)"
            );
            $stmt->execute([
                'full_name'      => $fullName,
                'email'          => $email,
                'password_hash'  => $passwordHash,
                'phone'          => $phone !== '' ? $phone : null,
                'reg_number'     => $regNumber,
                'faculty'        => $faculty !== '' ? $faculty : null,
                'level'          => $level !== '' ? $level : null,
            ]);

            setFlash('success', 'Registration successful! Please log in.');
            redirect('/auth/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration - Complaint System</title>
    <?php require __DIR__ . '/../includes/head_assets.php'; ?>
</head>
<body>
    <div class="auth-container">
        <h1>Student Registration</h1>

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
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>

            <label>Registration Number</label>
            <input type="text" name="reg_number" value="<?= e($_POST['reg_number'] ?? '') ?>" required>

            <label>Faculty</label>
            <input type="text" name="faculty" value="<?= e($_POST['faculty'] ?? '') ?>">

            <label>Level</label>
            <input type="text" name="level" value="<?= e($_POST['level'] ?? '') ?>" placeholder="e.g. 300">

            <label>Phone</label>
            <input type="text" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">

            <label>Password</label>
            <input type="password" name="password" required minlength="8">

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required minlength="8">

            <button type="submit" class="btn btn-primary w-full text-center">Register</button>
        </form>

        <p>Already have an account? <a href="<?= url('/auth/login.php') ?>">Login here</a></p>
    </div>
</body>
</html>
