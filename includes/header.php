<?php
/**
 * Shared header. Include AFTER requireRole() has run in the calling page,
 * and AFTER any redirect() logic (this outputs HTML).
 */
$pdo = getDBConnection();

// Unread notification count for the top bar
$unreadCount = 0;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = :uid AND is_read = 0");
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $unreadCount = (int) $stmt->fetch()['total'];
}

$role = $_SESSION['role'] ?? null;
$basePath = $role === 'student' ? '/student' : '/admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>Complaint System</title>

    <?php require __DIR__ . '/head_assets.php'; ?>
</head>
<body>
<header class="topbar">
    <div class="topbar-brand">Student Complaint &amp; Resolution Tracking System</div>
    <?php if (isLoggedIn()): ?>
    <nav class="topbar-nav">
        <?php if ($role === 'student'): ?>
            <a href="<?= url('/student/dashboard.php') ?>">Dashboard</a>
            <a href="<?= url('/student/submit_complaint.php') ?>">Submit Complaint</a>
            <a href="<?= url('/student/my_complaints.php') ?>">My Complaints</a>
            <a href="<?= url('/student/profile.php') ?>">Profile</a>
        <?php else: ?>
            <a href="<?= url('/admin/dashboard.php') ?>">Dashboard</a>
            <a href="<?= url('/admin/all_complaints.php') ?>">All Complaints</a>
            <a href="<?= url('/admin/reports.php') ?>">Reports</a>
            <?php if ($role === 'super_admin'): ?>
                <a href="<?= url('/admin/manage_departments.php') ?>">Departments</a>
                <a href="<?= url('/admin/manage_categories.php') ?>">Categories</a>
                <a href="<?= url('/admin/manage_users.php') ?>">Users</a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="<?= url($basePath . '/notifications.php') ?>" class="notif-link">
            Notifications
            <?php if ($unreadCount > 0): ?><span class="badge"><?= $unreadCount ?></span><?php endif; ?>
        </a>
        <span class="topbar-user">Hi, <?= e($_SESSION['full_name']) ?></span>
        <a href="<?= url('/auth/logout.php') ?>" class="btn-link">Logout</a>
    </nav>
    <?php endif; ?>
</header>
<main class="page-container">
    <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
