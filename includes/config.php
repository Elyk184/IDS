<?php
/**
 * Email Configuration
 * 
 * IMPORTANT: YOU MUST REPLACE THE EXAMPLE VALUES BELOW WITH YOUR ACTUAL GMAIL CREDENTIALS!
 * 
 * SETUP INSTRUCTIONS:
 * 1. Go to https://myaccount.google.com/security
 * 2. Make sure 2-Step Verification is enabled
 * 3. Go to App Passwords (under 2-Step Verification)
 * 4. Generate new App Password:
 *    - Select app: Mail
 *    - Select device: Windows Computer
 *    - Click Generate
 * 5. Copy the 16-character password
 * 
 * EXAMPLE FORMAT:
 * If Google gives you: "abcd efgh ijkl mnop"
 * You should enter: "abcdefghijklmnop" (remove all spaces)
 * 
 * COMMON ISSUES:
 * 1. Make sure you're using a Gmail address (must end with @gmail.com)
 * 2. Remove ALL spaces from the App Password
 * 3. The App Password must be exactly 16 characters
 * 4. Do not use your regular Gmail password
 * 5. 2-Step Verification must be enabled to use App Passwords
 */

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'Jhasenambogna119@gmail.com');  // ← REPLACE THIS with your Gmail address
define('SMTP_PASSWORD', 'eynvtgnwelmlswtq');      // ← REPLACE THIS with your 16-character App Password (NO SPACES)
define('SMTP_FROM', SMTP_USERNAME);               // Usually same as username

// Validate SMTP credentials
if (!filter_var(SMTP_USERNAME, FILTER_VALIDATE_EMAIL)) {
    error_log("Invalid email format in config.php");
    throw new Exception("Invalid email format in SMTP configuration. Must be a valid Gmail address.");
}

if (strlen(SMTP_PASSWORD) !== 16) {
    error_log("Invalid App Password length in config.php (should be exactly 16 characters, no spaces)");
    throw new Exception("Invalid App Password length. Must be exactly 16 characters with no spaces.");
}

if (!str_ends_with(strtolower(SMTP_USERNAME), '@gmail.com')) {
    error_log("Invalid email domain in config.php (must be @gmail.com)");
    throw new Exception("Invalid email domain. Must be a Gmail address (@gmail.com).");
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ids');

// Security Configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_TIME', 900); // 15 minutes
define('PASSWORD_RESET_TIMEOUT', 3600); // 1 hour

// System Configuration
define('SITE_NAME', 'Intrusion Detection System');
define('ADMIN_EMAIL', SMTP_USERNAME); // Use SMTP email as admin email
define('DEBUG_MODE', true);

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Time Zone
date_default_timezone_set('UTC');

// Headers for Security
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
if (isset($_SERVER['HTTPS'])) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
} 