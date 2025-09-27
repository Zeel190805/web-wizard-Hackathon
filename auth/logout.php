<?php
require_once '../config/config.php';

// Log activity before destroying session
if (is_logged_in()) {
    log_activity($_SESSION['user_id'], 'Logout', 'User logged out');
    
    // Clear remember me token if it exists
    if (isset($_COOKIE['remember_token'])) {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Remove remember me cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
}

// Destroy session
session_destroy();

// Set flash message
session_start();
$_SESSION['flash_message'] = 'You have been successfully logged out.';
$_SESSION['flash_type'] = 'success';

// Redirect to login page
header('Location: login.php');
exit();
?>
