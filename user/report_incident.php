<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

// Get user profile data
$user_id = $_SESSION['user_id'];
$profile_query = "SELECT email, phone FROM users WHERE id = ?";
$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_profile = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $severity = $_POST['severity'];
    $location = $_POST['location'];
    $date_time = date('Y-m-d H:i:s'); // Current date and time
    
    // Validation
    $errors = [];
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    if (empty($severity)) {
        $errors[] = "Severity is required";
    }
    if (empty($location)) {
        $errors[] = "Location is required";
    }

    // If no errors, insert the incident
    if (empty($errors)) {
        $query = "INSERT INTO incidents (title, description, location, date_time, severity, status, reported_by, created_at) 
                 VALUES (?, ?, ?, ?, ?, 'New', ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssss", $title, $description, $location, $date_time, $severity, $username);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Incident reported successfully!";
            header("Location: view_my_incidents.php");
            exit();
        } else {
            $errors[] = "Error reporting incident. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident | IDS</title>
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

        /* Form Styling */
        .form-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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

        .form-control:focus {
            box-shadow: none;
            border-color: #0d6efd;
        }

        .form-select:focus {
            box-shadow: none;
            border-color: #0d6efd;
        }

        /* Dashboard Header */
        .dashboard-header {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        /* Alert Styling */
        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
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
                    <a href="report_incident.php" class="active">
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
                    <a href="edit_profile.php">
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
                    <h4 class="mb-1">Report New Incident</h4>
                    <p class="text-muted mb-0">Please provide detailed information about the incident</p>
                </div>
                <a href="view_my_incidents.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-2"></i>Back to My Incidents
                </a>
            </div>

            <!-- Error Messages -->
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

            <!-- Incident Report Form -->
            <div class="form-card">
                <div class="card-header">
                    <h5 class="mb-0">Incident Details</h5>
                </div>
                <div class="card-body">
                    <form action="report_incident.php" method="POST">
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="title" name="title" placeholder="Title" 
                                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                            <label for="title">Incident Title</label>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="severity" name="severity" required>
                                        <option value="" disabled <?php echo !isset($_POST['severity']) ? 'selected' : ''; ?>>Select severity</option>
                                        <option value="Low" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'Low') ? 'selected' : ''; ?>>Low</option>
                                        <option value="Medium" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                                        <option value="High" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'High') ? 'selected' : ''; ?>>High</option>
                                        <option value="Critical" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'Critical') ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                    <label for="severity">Severity Level</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="location" name="location" 
                                           placeholder="Location" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" required>
                                    <label for="location">Incident Location</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-4">
                            <textarea class="form-control" id="description" name="description" 
                                      style="height: 200px" placeholder="Description" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <label for="description">Detailed Description</label>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-light me-md-2">
                                <i class="bi bi-x-circle me-2"></i>Clear Form
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-2"></i>Submit Report
                            </button>
                        </div>
                    </form>
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