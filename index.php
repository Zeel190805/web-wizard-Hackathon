<?php
require_once 'config/config.php';

// Redirect based on user role
if (is_logged_in()) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'student/dashboard.php');
    }
    exit();
} else {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}
?>
