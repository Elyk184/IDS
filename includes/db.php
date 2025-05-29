<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "intrusion_detection";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Function to sanitize input (protected against redeclaration)
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        global $conn;
        return htmlspecialchars(stripslashes(trim($conn->real_escape_string($data))));
    }
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
