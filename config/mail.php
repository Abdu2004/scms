<?php
/**
 * Gmail SMTP configuration for outgoing email notifications.
 *
 * ── HOW TO SET THIS UP ──────────────────────────────────────────────
 * Gmail does NOT allow SMTP login with your normal Gmail password.
 * You need an "App Password" instead:
 *
 *   1. Go to https://myaccount.google.com/security
 *   2. Turn ON "2-Step Verification" (required before App Passwords appear)
 *   3. Go to https://myaccount.google.com/apppasswords
 *   4. Create a new App Password (choose "Mail" as the app)
 *   5. Google gives you a 16-character password like: abcd efgh ijkl mnop
 *   6. Paste it below as SMTP_PASSWORD (remove the spaces)
 *
 * Do NOT use your real Gmail login password here — it will not work,
 * and Gmail actively blocks it for security reasons.
 * ────────────────────────────────────────────────────────────────────
 */

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls'); // Gmail uses STARTTLS on port 587
define('SMTP_USERNAME', 'youraddress@gmail.com');   // <-- change this
define('SMTP_PASSWORD', 'your16charapppassword');   // <-- change this (App Password, no spaces)
define('SMTP_FROM_EMAIL', SMTP_USERNAME);
define('SMTP_FROM_NAME', 'Student Complaint & Resolution Tracking System');

/**
 * Set to true only while actively testing email — it prints SMTP
 * conversation details to the page, which is useful for debugging
 * but should never be left on for real users.
 */
define('SMTP_DEBUG', false);
