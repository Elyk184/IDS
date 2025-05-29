<?php
require_once __DIR__ . '/includes/session_config.php';  // Must be included BEFORE session_start
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/captcha.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/vendor/autoload.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$registration_message = "";

// Generate new CAPTCHA only if:
// 1. It's not a POST request, or
// 2. The current CAPTCHA has been used (doesn't exist in session)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['captcha_answer'])) {
    generateCaptcha();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $email = sanitize_input($_POST['email']);
    $captcha = sanitize_input($_POST['captcha']);

    // Debug log
    error_log("Registration attempt - Username: $username, Email: $email");
    error_log("Session data: " . print_r($_SESSION, true));

    if (!verifyCaptcha($captcha)) {
        $registration_message = "<div class='alert alert-danger'>Invalid CAPTCHA. Please try again.</div>";
        generateCaptcha();
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if (!$stmt) {
            error_log("Error preparing statement: " . $conn->error);
            $registration_message = "<div class='alert alert-danger'>Database error. Please try again later.</div>";
            generateCaptcha();
        } else {
            $stmt->bind_param("ss", $username, $email);
            if (!$stmt->execute()) {
                error_log("Error executing statement: " . $stmt->error);
                $registration_message = "<div class='alert alert-danger'>Database error. Please try again later.</div>";
                generateCaptcha();
            } else {
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $registration_message = "<div class='alert alert-danger'>Username or email already exists.</div>";
                    generateCaptcha();
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $insert_stmt = $conn->prepare("INSERT INTO users (username, password, email, role, verified) VALUES (?, ?, ?, 'user', 0)");
                    if (!$insert_stmt) {
                        error_log("Error preparing insert statement: " . $conn->error);
                        $registration_message = "<div class='alert alert-danger'>Database error. Please try again later.</div>";
                        generateCaptcha();
                    } else {
                        $insert_stmt->bind_param("sss", $username, $hashed_password, $email);
                        if (!$insert_stmt->execute()) {
                            error_log("Error executing insert statement: " . $insert_stmt->error);
                            $registration_message = "<div class='alert alert-danger'>Database error. Please try again later.</div>";
                            generateCaptcha();
                        } else {
                            $user_id = $insert_stmt->insert_id;
                            $otp = rand(100000, 999999);
                            $expires_at = date('Y-m-d H:i:s', time() + 300); // 5 minutes expiry

                            $otp_stmt = $conn->prepare("INSERT INTO user_otps (user_id, otp, expires_at) VALUES (?, ?, ?)");
                            if (!$otp_stmt) {
                                error_log("Error preparing OTP statement: " . $conn->error);
                                $registration_message = "<div class='alert alert-danger'>Error generating OTP. Please try again.</div>";
                            } else {
                                $otp_stmt->bind_param("iss", $user_id, $otp, $expires_at);
                                if (!$otp_stmt->execute()) {
                                    error_log("Error executing OTP statement: " . $otp_stmt->error);
                                    $registration_message = "<div class='alert alert-danger'>Error generating OTP. Please try again.</div>";
                                } else {
                                    // Send email with OTP
                                    try {
                                        $mail = new PHPMailer(true);
                                        $mail->isSMTP();
                                        $mail->Host = SMTP_HOST;
                                        $mail->SMTPAuth = true;
                                        $mail->Username = SMTP_USERNAME;
                                        $mail->Password = SMTP_PASSWORD;
                                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                        $mail->Port = SMTP_PORT;

                                        $mail->setFrom(SMTP_FROM, 'Security System');
                                        $mail->addAddress($email, $username);
                                        $mail->isHTML(false);
                                        $mail->Subject = 'Your OTP for Security System Registration';
                                        $mail->Body = "Hello $username,\n\nYour OTP code is: $otp\nIt is valid for 5 minutes.\n\nThank you!";

                                        if (!$mail->send()) {
                                            error_log("Email error: " . $mail->ErrorInfo);
                                            throw new Exception("Failed to send email: " . $mail->ErrorInfo);
                                        }

                                        $_SESSION['otp_user_id'] = $user_id;
                                        header("Location: verify_otp.php");
                                        exit;
                                    } catch (Exception $e) {
                                        error_log("Email error: " . $e->getMessage());
                                        $registration_message = "<div class='alert alert-danger'>Failed to send OTP email. Please try again later.</div>";
                                    }
                                }
                                $otp_stmt->close();
                            }
                        }
                        $insert_stmt->close();
                    }
                }
                $stmt->close();
            }
        }
    }
}

function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Security System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <div class="register-container mt-5 p-4 bg-white shadow rounded">
        <h2 class="text-center mb-4">Create Account</h2>
        <?php echo $registration_message; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">What is <?php echo htmlspecialchars($_SESSION['captcha_question'] ?? ''); ?>?</label>
                <input type="text" name="captcha" class="form-control" required placeholder="Enter your answer">
            </div>

            <button type="submit" name="register" class="btn btn-primary w-100">Register</button>

            <div class="mt-3 text-center">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>
</div>
</body>
</html>
