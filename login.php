<?php
session_start();
include 'db.php';

$max_attempts = 3;
$lockout_time = 600; // 10 minutes in seconds
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

// Calculate lockout status
$remaining_attempts = $max_attempts - $attempts;
$lockout_message = "";
$disable_login = false;

if ($attempts >= $max_attempts) {
    $time_since_last_attempt = $time - $last_attempt;
    $remaining_time = $lockout_time - $time_since_last_attempt;

    if ($remaining_time > 0) {
        $minutes = floor($remaining_time / 60);
        $seconds = $remaining_time % 60;
        $lockout_message = "<div class='alert alert-danger'>Too many failed attempts. Try again in $minutes minutes and $seconds seconds.</div>";
        $disable_login = true;
    } else {
        // Reset lockout if time expired
        $conn->query("DELETE FROM login_attempts WHERE ip_address = '$ip_address'");
        $remaining_attempts = $max_attempts;
        $attempts = 0;
    }
}

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login']) && !$disable_login) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Verify user credentials
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($hash);

    if ($stmt->fetch() && password_verify($password, $hash)) {
        $_SESSION['username'] = $username;
        $conn->query("DELETE FROM login_attempts WHERE ip_address = '$ip_address'"); // Reset failed attempts
        $conn->query("INSERT INTO logs (timestamp, source_ip, destination_ip, protocol, alert) VALUES (NOW(), '$ip_address', 'Server', 'Login', 'Successful Login')");
        header("Location: index.php");
        exit();
    } else {
        $remaining_attempts--;

        // Update or insert failed login attempt
        $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) 
                                VALUES (?, 1, ?) 
                                ON DUPLICATE KEY UPDATE 
                                attempts = attempts + 1, last_attempt = ?");
        $stmt->bind_param("sii", $ip_address, $time, $time);
        $stmt->execute();
        $stmt->close();

        $conn->query("INSERT INTO logs (timestamp, source_ip, destination_ip, protocol, alert) VALUES (NOW(), '$ip_address', 'Server', 'Login', 'Failed Login Attempt')");

        if ($remaining_attempts > 0) {
            $lockout_message = "<div class='alert alert-warning'>Invalid credentials. You have $remaining_attempts attempts remaining.</div>";
        } else {
            $lockout_message = "<div class='alert alert-danger'>Too many failed attempts. You are locked out for 10 minutes.</div>";
        }
    }
}

// Handle user registration
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
        <h1 class="mb-4">Welcome</h1>
        <div id="login-form" class="card p-4 shadow-lg" style="width: 22rem;">
            <h4 class="mb-3">Login</h4>
            
            <?php echo $lockout_message; ?>  <!-- Show lockout/attempts warning inside login box -->

            <form method="POST">
                <input type="text" name="username" class="form-control mb-2" placeholder="Username" required <?php echo $disable_login ? 'disabled' : ''; ?>>
                <input type="password" name="password" class="form-control mb-2" placeholder="Password" required <?php echo $disable_login ? 'disabled' : ''; ?>>
                
                <button type="submit" name="login" class="btn btn-primary w-100" <?php echo $disable_login ? 'disabled' : ''; ?>>Login</button>
            </form>
            
            <?php if (!$disable_login): ?>
                <p class="text-muted mt-2">You have <strong><?php echo $remaining_attempts; ?></strong> attempts remaining.</p>
            <?php endif; ?>

            <hr>
            <button onclick="showRegisterForm()" class="btn btn-success w-100">Create New Account</button>
        </div>
        
        <div id="register-form" class="card p-4 shadow-lg mt-3" style="width: 22rem; display: none;">
            <h4 class="mb-3">Register</h4>
            <form method="POST">
                <input type="text" name="reg_username" class="form-control mb-2" placeholder="Username" required>
                <input type="password" name="reg_password" class="form-control mb-2" placeholder="Password" required>
                <button type="submit" name="register" class="btn btn-success w-100">Register</button>
            </form>
        </div>
    </div>
</body>
</html>
