<?php
/**
 * Database Connection & Self-Initialization Config
 * Uses PDO for secure prepared statements.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rental_ps');

try {
    // 1. Connect to MySQL Server (without selecting database first)
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // 2. Check if database exists, create if not
    $db_check = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    if (!$db_check->fetch()) {
        $pdo->exec("CREATE DATABASE `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    }

    // 3. Connect to the specific database
    $pdo->exec("USE `" . DB_NAME . "`");

    // 4. Check if tables exist by querying user table. If user table doesn't exist, import database.sql
    $table_check = $pdo->query("SHOW TABLES LIKE 'users'");
    if (!$table_check->fetch()) {
        $sql_file = dirname(__DIR__) . '/database.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            // Execute multi-query using exec
            $pdo->exec($sql);
        } else {
            die("Database initialization error: database.sql not found at " . $sql_file);
        }
    }

    // Export PDO instance globally
    $db = $pdo;

} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage() . "<br><br>Please make sure Apache and MySQL are running in your XAMPP Control Panel.");
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Utility function to check login
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Utility function for admin check
function check_admin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }
}

// Safe XSS Output Sanitization
function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Format Rupiah Currency
function format_rupiah($number) {
    return 'Rp' . number_format($number, 0, ',', '.');
}
?>
