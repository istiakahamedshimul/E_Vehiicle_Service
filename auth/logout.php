<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (is_logged_in()) {
    log_activity($_SESSION['user_id'], 'logout', 'User logged out');
    
    // Destroy session
    session_destroy();
    
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

set_flash_message('success', 'You have been logged out successfully.');
redirect('/index.php');
?>