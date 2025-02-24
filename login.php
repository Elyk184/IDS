<?php
session_start();
include 'db.php';

$max_attempts = 3;
$lockout_time = 600; // 10 minutes

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $time = time();

    // Check login attempts
    $stmt = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($attempts, $last_attempt);
    $stmt->fetch();
    $stmt->close();

    if ($attempts >= $max_attempts && $time - $last_attempt < $lockout_time) {
        echo "<div class='alert alert-danger'>Too many failed attempts. Try again later.</div>";
        exit();
    }

    // Verify user credentials
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($hash);

    if ($stmt->fetch() && password_verify($password, $hash)) {
        $_SESSION['username'] = $username;
        $conn->query("DELETE FROM login_attempts WHERE ip_address = '$ip_address'");
        $conn->query("INSERT INTO logs (timestamp, source_ip, destination_ip, protocol, alert) VALUES (NOW(), '$ip_address', 'Server', 'Login', 'Successful Login')");
        header("Location: index.php");
        exit();
    } else {
        $remaining_attempts = $max_attempts - ($attempts + 1);
        $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = ?");
        $stmt->bind_param("sii", $ip_address, $time, $time);
        $stmt->execute();
        $stmt->close();

        $conn->query("INSERT INTO logs (timestamp, source_ip, destination_ip, protocol, alert) VALUES (NOW(), '$ip_address', 'Server', 'Login', 'Failed Login Attempt')");
        
        if ($remaining_attempts > 0) {
            echo "<div class='alert alert-warning'>Invalid credentials. You have $remaining_attempts attempts remaining.</div>";
        } else {
            echo "<div class='alert alert-danger'>Too many failed attempts. You are locked out for 10 minutes.</div>";
        }
    }
}

// User registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = $_POST['reg_username'];
    $password = password_hash($_POST['reg_password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Registration successful! Please log in.</div>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login & Register</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        function showRegisterForm() {
            document.getElementById("register-form").style.display = "block";
            document.getElementById("login-form").style.display = "none";
        }
    </script>
</head>
<body class="container d-flex justify-content-center align-items-center vh-100">
    <div class="text-center">
        <h1 class="mb-4">Welcome Back</h1>
        <div id="login-form" class="card p-4 shadow-lg" style="width: 22rem;">
            <form method="POST">
                <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
                <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
                <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
            </form>
            <hr>
            <button onclick="showRegisterForm()" class="btn btn-success w-100">Create New Account</button>
        </div>
        
        <div id="register-form" class="card p-4 shadow-lg mt-3" style="width: 22rem; display: none;">
            <h2 class="mb-3">Register</h2>
            <form method="POST">
                <input type="text" name="reg_username" class="form-control mb-2" placeholder="Username" required>
                <input type="password" name="reg_password" class="form-control mb-2" placeholder="Password" required>
                <button type="submit" name="register" class="btn btn-success w-100">Register</button>
            </form>
        </div>
    </div>
</body>
</html>
