<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect($_SESSION['role'] === 'student' ? '/student/dashboard.php' : '/admin/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Student Complaint & Resolution Tracking System</title>
    <?php require __DIR__ . '/includes/head_assets.php'; ?>
</head>
<body class="landing-page">

<!-- ===== Nav ===== -->
<header class="lp-nav">
    <div class="lp-nav-inner">
        <div class="lp-logo">
            <span class="lp-logo-mark">CS</span>
            <span>Complaint System</span>
        </div>
        <div class="lp-nav-actions">
            <a href="<?= url('/auth/login.php') ?>" class="btn-link">Login</a>
            <a href="<?= url('/auth/register.php') ?>" class="btn btn-primary">Get Started</a>
        </div>
    </div>
</header>

<!-- ===== Hero ===== -->
<section class="lp-hero">
    <div class="lp-hero-inner">
        <div class="lp-hero-text">
            <span class="lp-eyebrow">For Students &amp; Administration</span>
            <h1 class="lp-h1">Every complaint, heard.<br>Every resolution, tracked.</h1>
            <p class="lp-sub">
                Submit complaints in seconds, follow their progress in real time, and get
                resolutions from the right department — no more lost paperwork, no more
                "come back next week."
            </p>
            <div class="landing-actions">
                <a href="<?= url('/auth/register.php') ?>" class="btn btn-primary btn-lg">Register as Student</a>
                <a href="<?= url('/auth/login.php') ?>" class="btn btn-lg">I Already Have an Account</a>
            </div>
            <div class="lp-trust">
                <span>&#10003; Real-time status tracking</span>
                <span>&#10003; Auto-routed to the right department</span>
                <span>&#10003; Full resolution history</span>
            </div>
        </div>
        <div class="lp-hero-art">
            <svg viewBox="0 0 480 360" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <!-- Card -->
                <rect x="40" y="60" width="400" height="260" rx="16" fill="#ffffff" stroke="#e4e4e7" stroke-width="2"/>
                <!-- Header bar -->
                <rect x="40" y="60" width="400" height="48" rx="16" fill="#f4f4f5"/>
                <rect x="40" y="92" width="400" height="16" fill="#f4f4f5"/>
                <circle cx="64" cy="84" r="8" fill="#d4d4d8"/>
                <rect x="84" y="78" width="120" height="12" rx="6" fill="#d4d4d8"/>
                <!-- Tracking code pill -->
                <rect x="64" y="128" width="140" height="24" rx="12" fill="#18181b"/>
                <text x="134" y="144" font-family="Inter,sans-serif" font-size="11" font-weight="600" fill="#fafafa" text-anchor="middle">CMP-2026-000123</text>
                <!-- Status badges -->
                <rect x="64" y="168" width="80" height="22" rx="11" fill="#fefce8" stroke="#ca8a04" stroke-opacity="0.2"/>
                <text x="104" y="183" font-family="Inter,sans-serif" font-size="10" font-weight="500" fill="#a16207" text-anchor="middle">Pending</text>
                <rect x="152" y="168" width="90" height="22" rx="11" fill="#eff6ff" stroke="#2563eb" stroke-opacity="0.2"/>
                <text x="197" y="183" font-family="Inter,sans-serif" font-size="10" font-weight="500" fill="#1d4ed8" text-anchor="middle">In Progress</text>
                <rect x="250" y="168" width="80" height="22" rx="11" fill="#f0fdf4" stroke="#16a34a" stroke-opacity="0.2"/>
                <text x="290" y="183" font-family="Inter,sans-serif" font-size="10" font-weight="500" fill="#15803d" text-anchor="middle">Resolved</text>
                <!-- Timeline -->
                <line x1="80" y1="220" x2="80" y2="290" stroke="#e4e4e7" stroke-width="2" stroke-dasharray="4 4"/>
                <circle cx="80" cy="226" r="6" fill="#18181b"/>
                <rect x="100" y="218" width="180" height="10" rx="5" fill="#d4d4d8"/>
                <rect x="100" y="234" width="120" height="8" rx="4" fill="#e4e4e7"/>
                <circle cx="80" cy="266" r="6" fill="#a1a1aa"/>
                <rect x="100" y="258" width="160" height="10" rx="5" fill="#e4e4e7"/>
                <rect x="100" y="274" width="100" height="8" rx="4" fill="#e4e4e7"/>
                <!-- Side card -->
                <rect x="300" y="210" width="120" height="90" rx="10" fill="#fafafa" stroke="#e4e4e7" stroke-width="1.5"/>
                <rect x="316" y="226" width="60" height="8" rx="4" fill="#a1a1aa"/>
                <rect x="316" y="242" width="88" height="6" rx="3" fill="#e4e4e7"/>
                <rect x="316" y="254" width="70" height="6" rx="3" fill="#e4e4e7"/>
                <rect x="316" y="270" width="50" height="14" rx="7" fill="#18181b"/>
            </svg>
        </div>
    </div>
</section>

<!-- ===== Features ===== -->
<section class="lp-section">
    <h2 class="lp-section-title">Everything you need, in one place</h2>
    <p class="lp-section-sub">Built to replace scattered emails, lost forms, and endless follow-up visits.</p>

    <div class="lp-features">
        <div class="lp-feature-card">
            <div class="lp-feature-icon lp-icon-blue">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12h6m-6 4h6M9 8h6M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <h3>Submit in Seconds</h3>
            <p>Pick a category, describe the issue, attach evidence if needed — done. No forms to print, no offices to queue at.</p>
        </div>

        <div class="lp-feature-card">
            <div class="lp-feature-icon lp-icon-amber">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <h3>Track in Real Time</h3>
            <p>Every complaint gets a tracking code. Watch it move from Pending &rarr; In Progress &rarr; Resolved, with a full timestamped history.</p>
        </div>

        <div class="lp-feature-card">
            <div class="lp-feature-icon lp-icon-green">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <h3>Routed Automatically</h3>
            <p>Complaints go straight to the right department — Hostel, Security, Bursary, Library — no guessing who to email.</p>
        </div>

        <div class="lp-feature-card">
            <div class="lp-feature-icon lp-icon-sky">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <h3>Stay Notified</h3>
            <p>Get notified the moment your complaint status changes — no need to keep checking back manually.</p>
        </div>
    </div>
</section>

<!-- ===== How it works ===== -->
<section class="lp-section lp-how">
    <h2 class="lp-section-title">How it works</h2>
    <p class="lp-section-sub">Three steps from complaint to resolution.</p>

    <div class="lp-steps">
        <div class="lp-step">
            <div class="lp-step-num">1</div>
            <h3>Submit</h3>
            <p>Log in, choose a category, and describe your issue. Get an instant tracking code.</p>
        </div>
        <div class="lp-step-connector"></div>
        <div class="lp-step">
            <div class="lp-step-num">2</div>
            <h3>Review</h3>
            <p>The right department reviews and assigns your complaint to a staff member.</p>
        </div>
        <div class="lp-step-connector"></div>
        <div class="lp-step">
            <div class="lp-step-num">3</div>
            <h3>Resolve</h3>
            <p>Get a resolution note and full status history — visible to you from start to finish.</p>
        </div>
    </div>
</section>

<!-- ===== CTA ===== -->
<section class="lp-cta">
    <div class="lp-cta-inner">
        <h2>Ready to get your voice heard?</h2>
        <p>Create your student account and submit your first complaint in under a minute.</p>
        <a href="<?= url('/auth/register.php') ?>" class="btn btn-primary btn-lg">Register Now</a>
    </div>
</section>

<!-- ===== Footer ===== -->
<footer class="lp-footer">
    <p>&copy; <?= date('Y') ?> Student Complaint &amp; Resolution Tracking System</p>
</footer>

</body>
</html>
