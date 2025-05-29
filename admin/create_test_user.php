<?php
require __DIR__ . '/../includes/db.php';

// Hash the password
$password = password_hash('test123', PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, password, email, role, verified) 
        VALUES (?, ?, ?, 'user', 1)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$username = 'test_user';
$email = 'test@example.com';
$stmt->bind_param("sss", $username, $password, $email);

if ($stmt->execute()) {
    echo "Test user created successfully. ID: " . $conn->insert_id;
    
    // Now create a test incident for this user
    $incident_sql = "INSERT INTO incident_reports (incident_type, severity, description, reported_by, timestamp, status) 
                    VALUES (?, ?, ?, ?, NOW(), 'pending')";
    
    $incident_stmt = $conn->prepare($incident_sql);
    if (!$incident_stmt) {
        die("Error preparing incident statement: " . $conn->error);
    }
    
    $incident_type = 'Test Incident';
    $severity = 'medium';
    $description = 'This is a test incident';
    
    $incident_stmt->bind_param("ssss", $incident_type, $severity, $description, $username);
    
    if ($incident_stmt->execute()) {
        echo "\nTest incident created successfully. ID: " . $conn->insert_id;
    } else {
        echo "\nError creating test incident: " . $incident_stmt->error;
    }
    
    $incident_stmt->close();
} else {
    echo "Error creating test user: " . $stmt->error;
}

$stmt->close();
?> 