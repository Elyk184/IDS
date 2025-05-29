<?php
require_once __DIR__ . '/../includes/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Creating incident reporting tables...\n";

// Create incident_reports table
$sql = "CREATE TABLE IF NOT EXISTS incident_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    incident_type ENUM('unauthorized_access', 'data_breach', 'malware', 'phishing', 'system_failure', 'other') NOT NULL,
    severity ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    description TEXT NOT NULL,
    reported_by VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'investigating', 'resolved', 'closed') DEFAULT 'pending',
    resolution_notes TEXT,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (reported_by) REFERENCES users(username) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "Incident reports table created successfully\n";
} else {
    echo "Error creating incident reports table: " . $conn->error . "\n";
}

// Create incident_attachments table for future use
$sql = "CREATE TABLE IF NOT EXISTS incident_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    incident_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incident_reports(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "Incident attachments table created successfully\n";
} else {
    echo "Error creating incident attachments table: " . $conn->error . "\n";
}

echo "Database setup completed.\n"; 