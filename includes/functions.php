<?php
/**
 * Shared helper functions.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * BASE_URL: the URL path to the app's root folder (e.g. "/scms"),
 * computed automatically from where this file sits on disk. Defined here
 * (rather than in config/database.php) so it's guaranteed to exist on
 * EVERY page, even ones that don't need a database connection.
 */
if (!defined('BASE_URL')) {
    $appRootFs = str_replace('\\', '/', dirname(__DIR__)); // this file is in includes/, so one level up = app root
    $docRoot   = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
    $baseUrl   = $docRoot !== '' ? str_replace($docRoot, '', $appRootFs) : '';
    define('BASE_URL', rtrim($baseUrl, '/'));
}

/** Build a full app-relative URL from a root-relative path, e.g. url('/student/dashboard.php'). */
function url(string $path): string
{
    return (defined('BASE_URL') ? BASE_URL : '') . $path;
}

/** Redirect to a given root-relative path and stop execution. */
function redirect(string $path): void
{
    header("Location: " . url($path));
    exit;
}

/** Store a one-time flash message in the session. */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Retrieve and clear the flash message, if any. */
function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Check whether a user is logged in. */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/** Guard a page so only logged-in users of a given role (or any role) can access it. */
function requireRole(array $allowedRoles = []): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to continue.');
        redirect('/auth/login.php');
    }

    if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles, true)) {
        setFlash('error', 'You do not have permission to access that page.');
        redirect('/auth/login.php');
    }
}

/** Sanitize a string for safe output in HTML. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF protection: one token per session, reused across all forms in that
 * session (not regenerated per-form) so multiple tabs/forms don't invalidate
 * each other.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Output a ready-to-use hidden <input> for a <form>. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Verify the CSRF token on an incoming POST request. Call this as the very
 * first thing inside `if ($_SERVER['REQUEST_METHOD'] === 'POST')`, before
 * any data is read or any action is taken. Redirects back with a flash
 * error on failure — it never lets a request without a valid token proceed.
 *
 * If $redirectTo is omitted, redirects to the current page's own URL
 * (REQUEST_URI) directly — NOT through redirect()/url(), because
 * REQUEST_URI already includes any subfolder (e.g. /scms/...), and
 * passing it through url() would prepend BASE_URL a second time.
 */
function verifyCsrfOrRedirect(?string $redirectTo = null): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    if ($submitted === '' || $expected === '' || !hash_equals($expected, $submitted)) {
        setFlash('error', 'Your session expired or the form was resubmitted. Please try again.');
        if ($redirectTo !== null) {
            redirect($redirectTo);
        }
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
}

/** Generate a unique complaint tracking code, e.g. CMP-2026-000123.
 *  Uses a transaction + SELECT ... FOR UPDATE to avoid race conditions
 *  when multiple students submit complaints concurrently. */
function generateTrackingCode(PDO $pdo): string
{
    $year = date('Y');
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM complaints WHERE YEAR(created_at) = :year FOR UPDATE");
        $stmt->execute(['year' => $year]);
        $count = (int) $stmt->fetch()['total'] + 1;
        $code = sprintf("CMP-%s-%06d", $year, $count);
        $pdo->commit();
        return $code;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Log a complaint status change into the history table. */
function logStatusChange(PDO $pdo, int $complaintId, ?string $oldStatus, string $newStatus, ?int $changedBy, ?string $remarks = null): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO complaint_status_history (complaint_id, old_status, new_status, changed_by, remarks)
         VALUES (:complaint_id, :old_status, :new_status, :changed_by, :remarks)"
    );
    $stmt->execute([
        'complaint_id' => $complaintId,
        'old_status'   => $oldStatus,
        'new_status'   => $newStatus,
        'changed_by'   => $changedBy,
        'remarks'      => $remarks,
    ]);
}

/**
 * Create a notification for a user, and — if channel is 'email' — actually
 * attempt to send it via PHP's mail(). This satisfies the project's stated
 * scope (Chapter 1.8: "application and email notifications, where necessary").
 *
 * mail() requires SMTP to be configured in WAMP's php.ini (or a local mail
 * relay). If sending fails, we log it and continue — a missing mail server
 * should never break the complaint workflow itself.
 */
function notifyUser(PDO $pdo, int $userId, string $message, ?int $complaintId = null, string $channel = 'in_app'): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, complaint_id, message, channel)
         VALUES (:user_id, :complaint_id, :message, :channel)"
    );
    $stmt->execute([
        'user_id'      => $userId,
        'complaint_id' => $complaintId,
        'message'      => $message,
        'channel'      => $channel,
    ]);

    if ($channel === 'email') {
        sendEmailNotification($pdo, $userId, $message);
    }
}

/** Look up a user's email and attempt to send them a notification email via Gmail SMTP. Fails silently (logged) on error. */
function sendEmailNotification(PDO $pdo, int $userId, string $message): bool
{
    $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || empty($user['email'])) {
        return false;
    }

    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
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
        $mail->SMTPDebug  = SMTP_DEBUG ? 2 : 0;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($user['email'], $user['full_name']);

        $mail->isHTML(false);
        $mail->Subject = 'Complaint System Notification';
        $mail->Body    = "Hello {$user['full_name']},\r\n\r\n{$message}\r\n\r\n"
                        . "Please log in to the Student Complaint & Resolution Tracking System to view details.\r\n";

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        // A misconfigured/unreachable SMTP server must never break the
        // complaint workflow itself — log and move on.
        error_log("Email notification failed to send to user #{$userId} ({$user['email']}): " . $mail->ErrorInfo);
        return false;
    }
}
