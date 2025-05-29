<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

echo "=== Session Information ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);

echo "\n=== Database Users ===\n";
$result = $conn->query("SELECT username, email, role, verified FROM users");
echo "All users in database:\n";
while ($row = $result->fetch_assoc()) {
    echo "Username: " . $row['username'] . "\n";
    echo "Email: " . $row['email'] . "\n";
    echo "Role: " . $row['role'] . "\n";
    echo "Verified: " . ($row['verified'] ? 'Yes' : 'No') . "\n";
    echo "-------------------\n";
}

echo "\n=== Current User Check ===\n";
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "Current user found in database:\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Verified: " . ($user['verified'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "WARNING: Session username '{$username}' not found in database!\n";
    }
    $stmt->close();
} else {
    echo "No user session found!\n";
}
?> 