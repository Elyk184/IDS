<?php
require_once __DIR__ . '/../includes/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Checking database connection...\n";

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS ids");
$conn->select_db("ids");

echo "Checking tables...\n";

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows === 0) {
    echo "Creating users table...\n";
    $sql = "CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
        verified TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY idx_username (username),
        UNIQUE KEY idx_email (email)
    )";
    if ($conn->query($sql)) {
        echo "Users table created successfully\n";
    } else {
        echo "Error creating users table: " . $conn->error . "\n";
    }
}

// Check if user_otps table exists
$result = $conn->query("SHOW TABLES LIKE 'user_otps'");
if ($result->num_rows === 0) {
    echo "Creating user_otps table...\n";
    $sql = "CREATE TABLE user_otps (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        otp VARCHAR(6) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        used TINYINT(1) DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    if ($conn->query($sql)) {
        echo "User_otps table created successfully\n";
    } else {
        echo "Error creating user_otps table: " . $conn->error . "\n";
    }
}

// Check if logs table exists
$result = $conn->query("SHOW TABLES LIKE 'logs'");
if ($result->num_rows === 0) {
    echo "Creating logs table...\n";
    $sql = "CREATE TABLE logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        source_ip VARCHAR(45) NOT NULL,
        destination_ip VARCHAR(45) NOT NULL,
        protocol VARCHAR(50) NOT NULL,
        alert TEXT NOT NULL,
        severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info'
    )";
    if ($conn->query($sql)) {
        echo "Logs table created successfully\n";
    } else {
        echo "Error creating logs table: " . $conn->error . "\n";
    }
}

// Check if system_settings table exists
$result = $conn->query("SHOW TABLES LIKE 'system_settings'");
if ($result->num_rows === 0) {
    echo "Creating system_settings table...\n";
    $sql = "CREATE TABLE system_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        setting_description TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    if ($conn->query($sql)) {
        echo "System_settings table created successfully\n";
    } else {
        echo "Error creating system_settings table: " . $conn->error . "\n";
    }
}

// Check table structures
echo "\nChecking table structures...\n";

$tables = ['users', 'user_otps', 'logs', 'system_settings'];
foreach ($tables as $table) {
    echo "\nStructure of $table table:\n";
    $result = $conn->query("DESCRIBE $table");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
}

echo "\nDatabase check completed.\n"; 