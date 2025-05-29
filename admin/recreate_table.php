<?php
require __DIR__ . '/../includes/db.php';

$conn->select_db('ids');

// Drop the table if it exists
$conn->query('DROP TABLE IF EXISTS incident_reports');

// Create the table
$create_table = "CREATE TABLE incident_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_type VARCHAR(50) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    description TEXT NOT NULL,
    reported_by VARCHAR(100) NOT NULL,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'investigating', 'resolved', 'closed') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (reported_by) REFERENCES users(username) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($create_table)) {
    echo "Table created successfully\n";
    
    // Verify the table structure
    $result = $conn->query('SHOW COLUMNS FROM incident_reports');
    if ($result) {
        echo "\nTable structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    }
} else {
    echo "Error creating table: " . $conn->error;
}
?> 