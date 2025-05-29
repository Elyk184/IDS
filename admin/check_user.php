<?php
require __DIR__ . '/../includes/db.php';

session_start();
$username = $_SESSION['username'] ?? 'unknown';

echo "Checking for user: " . $username . "\n";

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "User found:\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Role: " . $user['role'] . "\n";
} else {
    echo "User not found in database\n";
}

// Show all users in the database
echo "\nAll users in database:\n";
$all_users = $conn->query("SELECT username, email, role FROM users");
while ($user = $all_users->fetch_assoc()) {
    echo $user['username'] . " (" . $user['role'] . ")\n";
}

$stmt->close();
?> 