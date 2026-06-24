<?php
/**
 * Admin Logout Page
 */
require_once dirname(__DIR__) . '/config/database.php';

// Unset only admin session
unset($_SESSION['admin_logged_in']);

// Optional: clean all session if they logout
// But keeping user session might be good. Let's clean everything to be safe.
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}
session_destroy();

header("Location: login.php");
exit;
?>
