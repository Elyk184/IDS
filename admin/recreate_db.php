<?php
require_once __DIR__ . '/../includes/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting database recreation process...\n";

// Drop all tables first
$conn->query('SET FOREIGN_KEY_CHECKS = 0');
$tables = ['incident_reports', 'user_otps', 'system_settings', 'logs', 'login_attempts', 'users'];
foreach ($tables as $table) {
    if ($conn->query("DROP TABLE IF EXISTS $table")) {
        echo "Dropped table $table if it existed\n";
    } else {
        echo "Error dropping $table: " . $conn->error . "\n";
    }
}
$conn->query('SET FOREIGN_KEY_CHECKS = 1');

echo "All tables dropped. Creating new tables...\n";

// Create users table first
$create_users = "CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    login_attempts INT DEFAULT 0,
    last_failed_login DATETIME DEFAULT NULL,
    password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_username (username),
    UNIQUE KEY idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($create_users)) {
    echo "Created users table\n";
} else {
    die("Error creating users table: " . $conn->error . "\n");
}

// Create incident_reports table
$create_incidents = "CREATE TABLE incident_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_type VARCHAR(50) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    description TEXT NOT NULL,
    reported_by VARCHAR(255) NOT NULL,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'investigating', 'resolved', 'closed') NOT NULL DEFAULT 'pending',
    resolution_notes TEXT,
    resolved_at DATETIME DEFAULT NULL,
    resolved_by VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (reported_by) REFERENCES users(username) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(username) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($create_incidents)) {
    echo "Created incident_reports table\n";
} else {
    die("Error creating incident_reports table: " . $conn->error . "\n");
}

// Create other tables
$create_otps = "CREATE TABLE user_otps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    otp VARCHAR(6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    used TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($create_otps)) {
    echo "Created user_otps table\n";
} else {
    die("Error creating user_otps table: " . $conn->error . "\n");
}

$create_settings = "CREATE TABLE system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    setting_description TEXT DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (updated_by) REFERENCES users(username) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($create_settings)) {
    echo "Created system_settings table\n";
} else {
    die("Error creating system_settings table: " . $conn->error . "\n");
}

$create_logs = "CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    source_ip VARCHAR(45) NOT NULL,
    destination_ip VARCHAR(45) NOT NULL,
    protocol VARCHAR(50) NOT NULL,
    alert TEXT NOT NULL,
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    INDEX idx_timestamp (timestamp),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($create_logs)) {
    echo "Created logs table\n";
} else {
    die("Error creating logs table: " . $conn->error . "\n");
}

$create_login_attempts = "CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(255) DEFAULT NULL,
    attempts INT NOT NULL DEFAULT 0,
    last_attempt INT NOT NULL,
    blocked_until DATETIME DEFAULT NULL,
    UNIQUE KEY idx_ip (ip_address),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($create_login_attempts)) {
    echo "Created login_attempts table\n";
} else {
    die("Error creating login_attempts table: " . $conn->error . "\n");
}

// Insert default admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$insert_admin = $conn->prepare("INSERT INTO users (username, password, email, role, verified) VALUES (?, ?, ?, 'admin', 1)");
$admin_username = 'admin';
$admin_email = 'admin@example.com';
$insert_admin->bind_param("sss", $admin_username, $admin_password, $admin_email);

if ($insert_admin->execute()) {
    echo "Created default admin user\n";
} else {
    echo "Error creating admin user: " . $insert_admin->error . "\n";
}

// Insert default system settings
$default_settings = [
    ['smtp_host', 'smtp.gmail.com', 'SMTP server hostname'],
    ['smtp_port', '587', 'SMTP server port'],
    ['smtp_username', '', 'SMTP authentication username'],
    ['smtp_from', 'noreply@yourdomain.com', 'From email address for system notifications'],
    ['max_login_attempts', '3', 'Maximum number of failed login attempts before lockout'],
    ['lockout_time', '30', 'Account lockout duration in minutes'],
    ['password_expiry_days', '90', 'Number of days before password expires'],
    ['session_timeout', '30', 'Session timeout in minutes'],
    ['min_password_length', '8', 'Minimum password length'],
    ['require_special_chars', '1', 'Require special characters in password'],
    ['maintenance_mode', '0', 'System maintenance mode'],
    ['log_retention_days', '90', 'Number of days to keep logs']
];

$settings_stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_description) VALUES (?, ?, ?)");
foreach ($default_settings as $setting) {
    $settings_stmt->bind_param("sss", $setting[0], $setting[1], $setting[2]);
    if ($settings_stmt->execute()) {
        echo "Inserted setting: {$setting[0]}\n";
    } else {
        echo "Error inserting setting {$setting[0]}: " . $settings_stmt->error . "\n";
    }
}

// Create test user
$test_password = password_hash('user123', PASSWORD_DEFAULT);
$insert_test = $conn->prepare("INSERT INTO users (username, password, email, role, verified) VALUES (?, ?, ?, 'user', 1)");
$test_username = 'testuser';
$test_email = 'testuser@example.com';
$insert_test->bind_param("sss", $test_username, $test_password, $test_email);

if ($insert_test->execute()) {
    echo "Created test user\n";
} else {
    echo "Error creating test user: " . $insert_test->error . "\n";
}

echo "Database recreation completed successfully!\n"; 