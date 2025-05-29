<?php
require __DIR__ . '/../includes/db.php';

$incident_sql = "INSERT INTO incident_reports (incident_type, severity, description, reported_by, timestamp, status) 
                VALUES (?, ?, ?, ?, NOW(), 'pending')";

$incident_stmt = $conn->prepare($incident_sql);
if (!$incident_stmt) {
    die("Error preparing incident statement: " . $conn->error);
}

$incident_type = 'Test Incident';
$severity = 'medium';
$description = 'This is a test incident';
$username = 'test_user';

$incident_stmt->bind_param("ssss", $incident_type, $severity, $description, $username);

if ($incident_stmt->execute()) {
    echo "Test incident created successfully. ID: " . $conn->insert_id;
} else {
    echo "Error creating test incident: " . $incident_stmt->error;
}

$incident_stmt->close();
?> 