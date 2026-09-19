<?php
/**
 * Database connection using PDO.
 * Adjust DB_HOST / DB_NAME / DB_USER / DB_PASS for your WAMP setup.
 *
 * Note: BASE_URL is defined in includes/functions.php, not here, so that
 * every page gets it even if it doesn't need a database connection.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'complaint_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // default WAMP root password is usually empty

function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log the full error internally but never expose DB details to users
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('Sorry, the system is temporarily unavailable. Please try again later.');
        }
    }

    return $pdo;
}
