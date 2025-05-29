<?php
session_start();
require __DIR__ . '/includes/captcha.php';
require __DIR__ . '/includes/db.php';

// Debug log
error_log("Login process started");

// Generate captcha only if NOT a form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    generateCaptcha();
}

$login_message = "";

// Get system settings for login attempts and lockout time
$settings_query = "SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('max_login_attempts', 'lockout_time')";
$settings_result = $conn->query($settings_query);
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$max_attempts = isset($settings['max_login_attempts']) ? (int)$settings['max_login_attempts'] : 3;
$lockout_time = isset($settings['lockout_time']) ? (int)$settings['lockout_time'] * 60 : 600; // Convert minutes to seconds
$ip_address = $_SERVER['REMOTE_ADDR'];

// Debug log for settings
error_log("Login settings - Max attempts: $max_attempts, Lockout time: $lockout_time seconds");

// Check login attempts for IP
$stmt = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
$stmt->bind_param("s", $ip_address);
$stmt->execute();
$result = $stmt->get_result();
$attempt_data = $result->fetch_assoc();
$stmt->close();

$attempts = $attempt_data['attempts'] ?? 0;
$last_attempt = $attempt_data['last_attempt'] ?? 0;

// Check if user is locked out
if ($attempts >= $max_attempts) {
    $time_since_last_attempt = time() - $last_attempt;
    $remaining_time = $lockout_time - $time_since_last_attempt;
    if ($remaining_time > 0) {
        $minutes = floor($remaining_time / 60);
        $seconds = $remaining_time % 60;
        $login_message = "<div class='alert alert-danger'>Too many failed attempts. Try again in $minutes minutes and $seconds seconds.</div>";
    } else {
        // Unlock the user after lockout time
        $conn->query("DELETE FROM login_attempts WHERE ip_address = '$ip_address'");
        $attempts = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login']) && empty($login_message)) {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $captcha = sanitize_input($_POST['captcha']);

    // Debug log
    error_log("Login attempt for username: $username");

    if (!verifyCaptcha($captcha)) {
        $login_message = "<div class='alert alert-danger'>Invalid CAPTCHA. Please try again.</div>";
        generateCaptcha(); // regenerate for retry
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role, verified FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            error_log("User found in database: " . print_r($user, true));

            if (password_verify($password, $user['password'])) {
                if ($user['verified'] == 1) {
                    // Successful login: clear failed attempts for IP
                    $conn->query("DELETE FROM login_attempts WHERE ip_address = '$ip_address'");

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    error_log("Login successful. Session data: " . print_r($_SESSION, true));

                    // Log successful login
                    $conn->query("INSERT INTO logs (timestamp, source_ip, destination_ip, protocol, alert) 
                                VALUES (NOW(), '$ip_address', 'Server', 'Login', 'Successful Login for $username')");

                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header("Location: /IDS/admin/admin_dashboard.php");
                    } else {
                        header("Location: /IDS/user/user_dashboard.php");
                    }
                    exit();
                } else {
                    error_log("Login failed: Account not verified");
                    $login_message = "<div class='alert alert-danger'>Account not verified. Please contact admin.</div>";
                    generateCaptcha(); // regenerate for retry
                }
            } else {
                error_log("Login failed: Invalid password");
                handleFailedLogin($ip_address, $username);
                $remaining_attempts = $max_attempts - ($attempts + 1);
                if ($remaining_attempts > 0) {
                    $login_message = "<div class='alert alert-warning'>Invalid credentials. $remaining_attempts attempts remaining.</div>";
                } else {
                    $lockout_minutes = ceil($lockout_time / 60);
                    $login_message = "<div class='alert alert-danger'>Account locked for $lockout_minutes minutes.</div>";
                }
                generateCaptcha(); // regenerate for retry
            }
        } else {
            error_log("Login failed: User not found");
            handleFailedLogin($ip_address, $username);
            $login_message = "<div class='alert alert-warning'>Invalid credentials.</div>";
            generateCaptcha(); // regenerate for retry
        }
        $stmt->close();
    }
}

function handleFailedLogin($ip, $username) {
    global $conn;
    $time = time();
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt)
                            VALUES (?, 1, ?)
                            ON DUPLICATE KEY UPDATE
                            attempts = attempts + 1, last_attempt = ?");
    $stmt->bind_param("sii", $ip, $time, $time);
    $stmt->execute();
    $stmt->close();

    // Log failed attempt
    $conn->query("INSERT INTO logs (timestamp, source_ip, destination_ip, protocol, alert)
                  VALUES (NOW(), '$ip', 'Server', 'Login', 'Failed Login Attempt for $username')");
}

function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Security System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="login-container mt-5 p-4 bg-white shadow rounded">
        <h2 class="text-center mb-4">Security System Login</h2>
        <?php echo $login_message; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="captcha" class="form-label">What is <?php echo htmlspecialchars($_SESSION['captcha_question'] ?? ''); ?>?</label>
                <input type="text" name="captcha" class="form-control" required placeholder="Enter your answer">
            </div>

            <button type="submit" name="login" class="btn btn-primary w-100">Login</button>

            <div class="mt-3 text-center">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </form>
    </div>
</div>
</body>
</html>
