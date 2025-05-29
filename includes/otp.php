<?php
// Start session only if not started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';

function generateOTP($user_id) {
    global $conn;
    
    // Generate 6-digit OTP with leading zeros if needed
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiry = time() + 300; // OTP valid for 5 minutes
    
    // Store OTP and expiry time in users table
    $stmt = $conn->prepare("UPDATE users SET last_otp = ?, otp_expiry = ? WHERE id = ?");
    $stmt->bind_param("sii", $otp, $expiry, $user_id);
    $stmt->execute();
    $stmt->close();
    
    return $otp;
}

function verifyOTP($user_id, $otp) {
    global $conn;
    
    $current_time = time();
    $stmt = $conn->prepare("SELECT last_otp, otp_expiry FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Check if OTP matches and is not expired
        if ($row['last_otp'] === $otp && $row['otp_expiry'] > $current_time) {
            // Clear OTP fields after successful verification
            $clear_stmt = $conn->prepare("UPDATE users SET last_otp = NULL, otp_expiry = NULL, verified = 1 WHERE id = ?");
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();
            return true;
        }
    }
    return false;
}

function sendOTPEmail($email, $username, $otp) {
    $subject = "Your OTP for Security System Registration";
    $message = "Hello $username,\n\nYour OTP code is: $otp\nIt is valid for 5 minutes.\n\nThank you!";
    $headers = "From: no-reply@yourdomain.com\r\n" .
               "Reply-To: no-reply@yourdomain.com\r\n" .
               "X-Mailer: PHP/" . phpversion();

    // Use mail() or a better mailing library like PHPMailer if mail() doesn't work on your server
    return mail($email, $subject, $message, $headers);
}
