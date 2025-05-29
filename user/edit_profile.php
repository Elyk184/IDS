<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user profile data
$profile_query = "SELECT email, phone, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_profile = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = [];
    
    // Profile Update
    if (isset($_POST['update_profile'])) {
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($errors)) {
            $update_query = "UPDATE users SET email = ?, phone = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssi", $email, $phone, $user_id);
            
            if ($stmt->execute()) {
                $success[] = "Profile updated successfully";
                $user_profile['email'] = $email;
                $user_profile['phone'] = $phone;
            } else {
                $errors[] = "Error updating profile";
            }
        }
    }
    
    // Password Change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $verify_query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!password_verify($current_password, $result['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters long";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success[] = "Password changed successfully";
            } else {
                $errors[] = "Error changing password";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | IDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 60px;
            --primary-bg: #f8f9fa;
            --sidebar-bg: #2c3034;
        }

        body {
            min-height: 100vh;
            background-color: var(--primary-bg);
        }

        /* Layout Components */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            padding: 1.5rem;
            color: #fff;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }

        /* Profile Section */
        .profile-section {
            text-align: center;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 2rem;
        }

        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid rgba(255,255,255,0.2);
        }

        /* Navigation */
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: #fff;
        }

        .nav-menu i {
            margin-right: 12px;
            font-size: 1.2rem;
        }

        /* Dashboard Header */
        .dashboard-header {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        /* Form Cards */
        .form-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .form-card .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1.5rem;
        }

        .form-card .card-body {
            padding: 2rem;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: none;
            border-color: #0d6efd;
        }

        /* Alert Styling */
        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        /* Profile Overview Card */
        .profile-overview {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-overview .profile-image {
            width: 150px;
            height: 150px;
            margin-bottom: 1.5rem;
        }

        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0,0,0,0.1);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Profile Section -->
            <div class="profile-section">
                <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($user_profile['email']))); ?>?s=200&d=mp" 
                     alt="Profile" class="profile-image">
                <h5 class="mb-1 text-white"><?php echo htmlspecialchars($username); ?></h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($user_profile['email']); ?></p>
            </div>

            <!-- Navigation -->
            <ul class="nav-menu">
                <li>
                    <a href="user_dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="report_incident.php">
                        <i class="bi bi-exclamation-triangle"></i>
                        Report Incident
                    </a>
                </li>
                <li>
                    <a href="view_my_incidents.php">
                        <i class="bi bi-list-ul"></i>
                        My Incidents
                    </a>
                </li>
                <li>
                    <a href="edit_profile.php" class="active">
                        <i class="bi bi-person-gear"></i>
                        Profile Settings
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Dashboard Header -->
            <div class="dashboard-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Profile Settings</h4>
                    <p class="text-muted mb-0">Manage your account settings and security</p>
                </div>
            </div>

            <!-- Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Error</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php foreach ($success as $message): ?>
                        <?php echo htmlspecialchars($message); ?><br>
                    <?php endforeach; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Profile Overview -->
            <div class="profile-overview">
                <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($user_profile['email']))); ?>?s=200&d=mp" 
                     alt="Profile" class="profile-image">
                <h4 class="mb-1"><?php echo htmlspecialchars($username); ?></h4>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($user_profile['email']); ?></p>
                <p class="mb-0">
                    <i class="bi bi-calendar3 me-2"></i>
                    Member since <?php echo date('F Y', strtotime($user_profile['created_at'])); ?>
                </p>
            </div>

            <div class="row">
                <!-- Update Profile Form -->
                <div class="col-md-6">
                    <div class="form-card">
                        <div class="card-header">
                            <h5 class="mb-0">Update Profile</h5>
                        </div>
                        <div class="card-body">
                            <form action="edit_profile.php" method="POST">
                                <div class="form-floating mb-4">
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="Email" value="<?php echo htmlspecialchars($user_profile['email']); ?>" required>
                                    <label for="email">Email Address</label>
                                </div>

                                <div class="form-floating mb-4">
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           placeholder="Phone" value="<?php echo htmlspecialchars($user_profile['phone']); ?>">
                                    <label for="phone">Phone Number</label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password Form -->
                <div class="col-md-6">
                    <div class="form-card">
                        <div class="card-header">
                            <h5 class="mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form action="edit_profile.php" method="POST">
                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="current_password" 
                                           name="current_password" placeholder="Current Password" required>
                                    <label for="current_password">Current Password</label>
                                </div>

                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" placeholder="New Password" required>
                                    <label for="new_password">New Password</label>
                                </div>

                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" placeholder="Confirm Password" required>
                                    <label for="confirm_password">Confirm New Password</label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="bi bi-lock me-2"></i>Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add mobile responsiveness for sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const toggleSidebar = () => {
                document.querySelector('.sidebar').classList.toggle('show');
            };

            // Add toggle button for mobile if needed
            if (window.innerWidth <= 768) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'btn btn-primary position-fixed top-0 start-0 m-2 d-md-none';
                toggleBtn.innerHTML = '<i class="bi bi-list"></i>';
                toggleBtn.onclick = toggleSidebar;
                document.body.appendChild(toggleBtn);
            }
        });
    </script>
</body>
</html>

<?php 
$stmt->close();
$conn->close();
?> 