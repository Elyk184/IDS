<?php
session_start();
require __DIR__ . '/includes/db.php';

// If not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user role and redirect accordingly
$role = $_SESSION['role'] ?? 'user';

if ($role === 'admin') {
    header("Location: admin/admin_dashboard.php");
} else {
    header("Location: user/user_dashboard.php");
}
exit();