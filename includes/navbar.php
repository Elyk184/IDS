<?php
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['username']);
$current_user = $_SESSION['username'] ?? '';
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-shield-check me-2"></i>
            <?php echo SITE_NAME; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if ($is_logged_in): ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'report_incident' ? 'active' : ''; ?>" href="report_incident.php">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Report Incident
                        </a>
                    </li>
                    <?php if ($is_admin): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'admin' ? 'active' : ''; ?>" href="admin/index.php">
                            <i class="bi bi-gear me-1"></i>
                            Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($current_user); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="bi bi-person me-2"></i>
                                    Profile
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'login' ? 'active' : ''; ?>" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>
                            Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'register' ? 'active' : ''; ?>" href="register.php">
                            <i class="bi bi-person-plus me-1"></i>
                            Register
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav> 