<?php
require __DIR__ . '/../includes/db.php';

// Hash the password
$password = password_hash('user123', PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, password, email, role, verified) 
        VALUES (?, ?, ?, 'user', 1)
        ON DUPLICATE KEY UPDATE
        password = VALUES(password),
        email = VALUES(email),
        verified = VALUES(verified)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$username = 'testuser';
$email = 'testuser@example.com';
$stmt->bind_param("sss", $username, $password, $email);

if ($stmt->execute()) {
    echo "Verified user created/updated successfully.\n";
    echo "Username: testuser\n";
    echo "Password: user123\n";
    echo "\nPlease use these credentials to log in and submit an incident report.";
} else {
    echo "Error creating user: " . $stmt->error;
}

$stmt->close();
?> 