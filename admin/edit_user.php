<?php
session_start();
require __DIR__ . '/../includes/db.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = "";
$user = null;

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $verified = isset($_POST['verified']) ? 1 : 0;
    
    // Validate inputs
    if (empty($username) || empty($email)) {
        $message = "<div class='alert alert-danger'>Username and email are required.</div>";
    } else {
        // Check if username or email already exists for other users
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check_stmt->bind_param("ssi", $username, $email, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = "<div class='alert alert-danger'>Username or email already exists.</div>";
        } else {
            // Update user
            $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, verified = ? WHERE id = ?");
            $update_stmt->bind_param("sssii", $username, $email, $role, $verified, $user_id);
            
            if ($update_stmt->execute()) {
                $message = "<div class='alert alert-success'>User updated successfully.</div>";
                
                // Update password if provided
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $pass_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $pass_stmt->bind_param("si", $password, $user_id);
                    $pass_stmt->execute();
                }
            } else {
                $message = "<div class='alert alert-danger'>Error updating user.</div>";
            }
        }
    }
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// If user not found, redirect to users list
if (!$user) {
    header("Location: manage_users.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit User - Admin Dashboard</title>
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
                            <a class="nav-link active" href="manage_users.php">
                                <i class="bi bi-people me-2"></i>Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_incidents.php">
                                <i class="bi bi-exclamation-triangle me-2"></i>Incident Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
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
                    <h2><i class="bi bi-person-gear me-2"></i>Edit User</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="manage_users.php">Users</a></li>
                                <li class="breadcrumb-item active">Edit User</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <a href="manage_users.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Users
                    </a>
                    </div>
                </div>

                <?php echo $message; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    <div class="invalid-feedback">Username is required.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    <div class="invalid-feedback">Valid email is required.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                                    <small class="text-muted">Minimum 8 characters</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role" required>
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                    </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="verified" id="verified" <?php echo $user['verified'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="verified">Account Verified</label>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between">
                                <a href="manage_users.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Save Changes
                            </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
</html>