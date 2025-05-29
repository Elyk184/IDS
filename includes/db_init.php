<?php
// Database initialization file
require_once __DIR__ . '/db.php';

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS ids";
if (!$conn->query($sql)) {
    error_log("Error creating database: " . $conn->error);
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db('ids');

// Drop and recreate incident_reports table
$drop_table = "DROP TABLE IF EXISTS incident_reports";
if (!$conn->query($drop_table)) {
    error_log("Error dropping incident_reports table: " . $conn->error);
    die("Error dropping incident_reports table: " . $conn->error);
}

// Create incident_reports table
$create_incident_reports = "CREATE TABLE incident_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_type VARCHAR(50) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    description TEXT NOT NULL,
    reported_by VARCHAR(100) NOT NULL,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'investigating', 'resolved', 'closed') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (reported_by) REFERENCES users(username) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Execute table creation
if (!$conn->query($create_incident_reports)) {
    error_log("Error creating incident_reports table: " . $conn->error);
    die("Error creating incident_reports table: " . $conn->error);
}

// Create logs table if not exists
$create_logs = "CREATE TABLE IF NOT EXISTS logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    timestamp DATETIME NOT NULL,
    source_ip VARCHAR(45),
    destination_ip VARCHAR(45),
    protocol VARCHAR(20),
    alert TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_timestamp (timestamp),
    INDEX idx_protocol (protocol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Execute table creation
if (!$conn->query($create_logs)) {
    error_log("Error creating logs table: " . $conn->error);
    die("Error creating logs table: " . $conn->error);
}

// Log table verification
$result = $conn->query("SHOW TABLES");
if ($result) {
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    error_log("Database tables initialized: " . implode(", ", $tables));
}

// Log table structure verification
$result = $conn->query("DESCRIBE incident_reports");
if ($result) {
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'] . " (" . $row['Type'] . ")";
    }
    error_log("incident_reports table structure: " . implode(", ", $columns));
}

error_log("Database initialization completed successfully");
?> 