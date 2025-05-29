<?php
require_once __DIR__ . '/includes/session_config.php';  // Must be included BEFORE session_start
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$verification_message = "";

if (!isset($_SESSION['otp_user_id'])) {
    header("Location: register.php");
    exit();
}

$user_id = $_SESSION['otp_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_otp = trim($_POST['otp']);

    $stmt = $conn->prepare("SELECT otp, expires_at FROM user_otps WHERE user_id = ? AND used = 0 ORDER BY created_at DESC LIMIT 1");
    if (!$stmt) {
        $verification_message = "<div class='alert alert-danger'>Database error. Please try again.</div>";
    } else {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if ($row['otp'] === $input_otp && strtotime($row['expires_at']) > time()) {
                // Mark OTP as used
                $update_otp = $conn->prepare("UPDATE user_otps SET used = 1 WHERE user_id = ? AND otp = ?");
                $update_otp->bind_param("is", $user_id, $input_otp);
                $update_otp->execute();
                $update_otp->close();

                // Mark user as verified
                $update_user = $conn->prepare("UPDATE users SET verified = 1 WHERE id = ?");
                $update_user->bind_param("i", $user_id);
                $update_user->execute();
                $update_user->close();

                // Clear session
                unset($_SESSION['otp_user_id']);
                
                // Redirect to login with success message
                header("Location: login.php?verified=1");
                exit();
            } else {
                if (strtotime($row['expires_at']) <= time()) {
                    $verification_message = "<div class='alert alert-danger'>OTP has expired. Please register again.</div>";
                    // Delete expired OTP
                    $delete_otp = $conn->prepare("DELETE FROM user_otps WHERE user_id = ?");
                    $delete_otp->bind_param("i", $user_id);
                    $delete_otp->execute();
                    $delete_otp->close();
                } else {
                    $verification_message = "<div class='alert alert-danger'>Invalid OTP. Please try again.</div>";
                }
            }
        } else {
            $verification_message = "<div class='alert alert-danger'>No valid OTP found. Please register again.</div>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .otp-container {
            max-width: 400px;
            margin: auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="otp-container">
            <h2 class="text-center mb-4">
                <i class="bi bi-shield-check me-2"></i>
                Verify Your Account
            </h2>
            <?php echo $verification_message; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Enter OTP sent to your email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="text" name="otp" class="form-control" required 
                               maxlength="6" pattern="\d{6}" 
                               title="Please enter the 6-digit OTP"
                               placeholder="Enter 6-digit OTP">
                    </div>
                    <div class="form-text">Check your email for the OTP code. Valid for 5 minutes.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-circle me-2"></i>
                    Verify
                </button>
            </form>
        </div>
    </div>
</body>
</html> 