<?php
require_once __DIR__ . '/session_config.php';
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

error_log("Header loaded. Session data: " . print_r($_SESSION, true));
error_log("Current session ID: " . session_id());

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    error_log("No user session found, redirecting to login");
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'user';

error_log("User info from session - ID: $user_id, Username: $username, Role: $role");

// Verify user exists in database
$check_user = $conn->prepare("SELECT username, role FROM users WHERE id = ? AND username = ?");
$check_user->bind_param("is", $user_id, $username);
$check_user->execute();
$user_result = $check_user->get_result();

if ($user_result->num_rows === 0) {
    error_log("User not found in database, clearing session");
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$check_user->close();

// Check if user has access to admin section
if (basename(dirname($_SERVER['PHP_SELF'])) === 'admin' && $role !== 'admin') {
    error_log("Non-admin user attempting to access admin section");
    header("Location: /IDS/user/user_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
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
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-center mb-4">Security Portal</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <?php if ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>" href="/IDS/admin/admin_dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_page === 'users' ? 'active' : ''; ?>" href="/IDS/admin/manage_users.php">
                                <i class="bi bi-people me-2"></i>Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_page === 'incidents' ? 'active' : ''; ?>" href="/IDS/admin/view_incidents.php">
                                <i class="bi bi-exclamation-triangle me-2"></i>View Incidents
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_page === 'settings' ? 'active' : ''; ?>" href="/IDS/admin/settings.php">
                                <i class="bi bi-gear me-2"></i>Settings
                            </a>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>" href="/IDS/user/user_dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_page === 'report' ? 'active' : ''; ?>" href="/IDS/user/report_incident.php">
                                <i class="bi bi-exclamation-triangle me-2"></i>Report Incident
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_page === 'profile' ? 'active' : ''; ?>" href="/IDS/user/profile.php">
                                <i class="bi bi-person me-2"></i>Profile
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="/IDS/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="bi <?php echo $page_icon ?? 'bi-shield-lock'; ?> me-2"></i><?php echo $page_title ?? 'Dashboard'; ?>
                    </h2>
                    <div>
                        <span class="badge bg-primary">
                            <i class="bi bi-person me-1"></i>
                            <?php echo htmlspecialchars($username); ?> (<?php echo ucfirst($role); ?>)
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 