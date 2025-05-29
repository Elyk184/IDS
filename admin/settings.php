<?php
session_start();
require __DIR__ . '/../includes/db.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update email settings
    if (isset($_POST['email_settings'])) {
        $smtp_host = trim($_POST['smtp_host']);
        $smtp_port = (int)$_POST['smtp_port'];
        $smtp_user = trim($_POST['smtp_username']);
        $smtp_pass = trim($_POST['smtp_password']);
        $smtp_from = trim($_POST['smtp_from']);
        
        // Update SMTP settings
        $settings = [
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_username' => $smtp_user,
            'smtp_from' => $smtp_from
        ];
        
        // Only update password if provided
        if (!empty($smtp_pass)) {
            $settings['smtp_password'] = $smtp_pass;
        }
        
        $success = true;
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                                  VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $message = "<div class='alert alert-success'>Email settings updated successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error updating email settings.</div>";
        }
    }
    
    // Update security settings
    if (isset($_POST['security_settings'])) {
        $max_login_attempts = (int)$_POST['max_login_attempts'];
        $lockout_time = (int)$_POST['lockout_time'];
        $password_expiry = (int)$_POST['password_expiry'];
        
        $settings = [
            'max_login_attempts' => $max_login_attempts,
            'lockout_time' => $lockout_time,
            'password_expiry_days' => $password_expiry
        ];
        
        $success = true;
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                                  VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $message = "<div class='alert alert-success'>Security settings updated successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error updating security settings.</div>";
        }
    }
}

// Get current settings
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Settings - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
        }
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-center mb-4">Admin Portal</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">
                                <i class="bi bi-people me-2"></i>Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_incidents.php">
                                <i class="bi bi-exclamation-triangle me-2"></i>Incident Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="settings.php">
                                <i class="bi bi-gear me-2"></i>Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logs.php">
                                <i class="bi bi-journal-text me-2"></i>System Logs
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-gear me-2"></i>System Settings</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Settings</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                <?php echo $message; ?>

                <!-- Email Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-envelope me-2"></i>Email Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" name="smtp_host" 
                                           value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="number" class="form-control" name="smtp_port" 
                                           value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SMTP Username</label>
                                    <input type="text" class="form-control" name="smtp_username" 
                                           value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SMTP Password</label>
                                    <input type="password" class="form-control" name="smtp_password" 
                                           placeholder="Leave blank to keep current password">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">From Email Address</label>
                                <input type="email" class="form-control" name="smtp_from" 
                                       value="<?php echo htmlspecialchars($settings['smtp_from'] ?? ''); ?>" required>
                            </div>
                            <button type="submit" name="email_settings" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Save Email Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-shield-lock me-2"></i>Security Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Maximum Login Attempts</label>
                                    <input type="number" class="form-control" name="max_login_attempts" 
                                           value="<?php echo htmlspecialchars($settings['max_login_attempts'] ?? '3'); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Account Lockout Time (minutes)</label>
                                    <input type="number" class="form-control" name="lockout_time" 
                                           value="<?php echo htmlspecialchars($settings['lockout_time'] ?? '30'); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Password Expiry (days)</label>
                                    <input type="number" class="form-control" name="password_expiry" 
                                           value="<?php echo htmlspecialchars($settings['password_expiry_days'] ?? '90'); ?>" required>
                                </div>
                            </div>
                            <button type="submit" name="security_settings" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Save Security Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 