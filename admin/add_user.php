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
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    $verified = isset($_POST['verified']) ? 1 : 0;
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        $message = "<div class='alert alert-danger'>All fields are required.</div>";
    } elseif (strlen($password) < 8) {
        $message = "<div class='alert alert-danger'>Password must be at least 8 characters long.</div>";
    } else {
        // Check if username or email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = "<div class='alert alert-danger'>Username or email already exists.</div>";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, role, verified) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssi", $username, $email, $hashed_password, $role, $verified);
            
            if ($insert_stmt->execute()) {
                $message = "<div class='alert alert-success'>User created successfully.</div>";
                // Clear form data on success
                $username = $email = '';
                $role = 'user';
                $verified = 0;
            } else {
                $message = "<div class='alert alert-danger'>Error creating user.</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add User - Admin Dashboard</title>
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
                        <h2><i class="bi bi-person-plus me-2"></i>Add New User</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="manage_users.php">Users</a></li>
                                <li class="breadcrumb-item active">Add User</li>
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
                                    <input type="text" class="form-control" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                                    <div class="invalid-feedback">Username is required.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                                    <div class="invalid-feedback">Valid email is required.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required>
                                    <small class="text-muted">Minimum 8 characters</small>
                                    <div class="invalid-feedback">Password is required.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role" required>
                                        <option value="user" <?php echo (isset($role) && $role === 'user') ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo (isset($role) && $role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="verified" id="verified" <?php echo (isset($verified) && $verified) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="verified">Account Verified</label>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between">
                                <a href="manage_users.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus me-2"></i>Create User
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