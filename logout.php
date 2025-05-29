<?php
session_start();

// Log the logout
if (isset($_SESSION['username'])) {
    require __DIR__ . '/includes/db.php';
    $username = $_SESSION['username'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Log the logout event
    $conn->query("INSERT INTO logs (timestamp, source_ip, destination_ip, protocol, alert) 
                  VALUES (NOW(), '$ip_address', 'Server', 'Logout', 'User $username logged out')");
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: /IDS/login.php");
exit();
?>