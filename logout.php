<?php
session_start();

// Destroy the session
$_SESSION = array(); // Unset all session variables

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect to the login page with a logged-out message
header("Location: index.php?logged_out=true");
exit();
?> 