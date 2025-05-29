<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get admin profile data
$profile_query = "SELECT email, phone FROM users WHERE id = ?";
$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_profile = $stmt->get_result()->fetch_assoc();

// Get total incidents count
$total_incidents_query = "SELECT COUNT(*) as total FROM incidents";
$result = $conn->query($total_incidents_query);
$total_incidents = $result->fetch_assoc()['total'];

// Get incidents by status
$status_query = "SELECT status, COUNT(*) as count FROM incidents GROUP BY status";
$status_result = $conn->query($status_query);
$status_counts = [];
while ($row = $status_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}

// Get total users count
$total_users_query = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$result = $conn->query($total_users_query);
$total_users = $result->fetch_assoc()['total'];

// Get recent incidents
$recent_incidents_query = "SELECT i.*, 
    (SELECT COUNT(*) FROM incident_notes WHERE incident_id = i.id) as note_count 
    FROM incidents i 
    ORDER BY i.created_at DESC LIMIT 5";
$recent_incidents = $conn->query($recent_incidents_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | IDS</title>
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

        /* Stats Cards */
        .stats-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 1.5rem;
            height: 100%;
            transition: transform 0.2s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        /* Recent Incidents Table */
        .incidents-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-top: 2rem;
        }

        .incidents-card .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1.5rem;
        }

        .table th {
            font-weight: 600;
            color: #495057;
            border-top: none;
        }

        /* Badge Styling */
        .badge {
            padding: 0.5em 1em;
            font-weight: 500;
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
                <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($admin_profile['email']))); ?>?s=200&d=mp" 
                     alt="Profile" class="profile-image">
                <h5 class="mb-1 text-white"><?php echo htmlspecialchars($username); ?></h5>
                <p class="text-muted mb-0">Administrator</p>
            </div>

            <!-- Navigation -->
            <ul class="nav-menu">
                <li>
                    <a href="admin_dashboard.php" class="active">
                        <i class="bi bi-speedometer2"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="view_incidents.php">
                        <i class="bi bi-shield-check"></i>
                        Manage Incidents
                    </a>
                </li>
                <li>
                    <a href="manage_users.php">
                        <i class="bi bi-people"></i>
                        Manage Users
                    </a>
                </li>
                <li>
                    <a href="logs.php">
                        <i class="bi bi-journal-text"></i>
                        System Logs
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="bi bi-gear"></i>
                        Settings
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
                    <h4 class="mb-1">Dashboard Overview</h4>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($username); ?></p>
                </div>
                <div>
                    <a href="view_incidents.php" class="btn btn-primary">
                        <i class="bi bi-shield-plus me-2"></i>View All Incidents
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4">
                <!-- Total Incidents -->
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-shield"></i>
                        </div>
                        <h3 class="mb-2"><?php echo $total_incidents; ?></h3>
                        <p class="text-muted mb-0">Total Incidents</p>
                    </div>
                </div>

                <!-- New Incidents -->
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <h3 class="mb-2"><?php echo $status_counts['New'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">New Incidents</p>
                    </div>
                </div>

                <!-- In Progress -->
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h3 class="mb-2"><?php echo $status_counts['In Progress'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">In Progress</p>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-people"></i>
                        </div>
                        <h3 class="mb-2"><?php echo $total_users; ?></h3>
                        <p class="text-muted mb-0">Total Users</p>
                    </div>
                </div>
            </div>

            <!-- Recent Incidents -->
            <div class="incidents-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Incidents</h5>
                    <span class="badge bg-primary"><?php echo $total_incidents; ?> Total</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Reported By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_incidents->num_rows > 0): ?>
                                <?php while ($incident = $recent_incidents->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $incident['id']; ?></td>
                                        <td><?php echo htmlspecialchars($incident['title']); ?></td>
                                        <td><?php echo htmlspecialchars($incident['location']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($incident['severity']) {
                                                    case 'Critical': echo 'danger'; break;
                                                    case 'High': echo 'warning'; break;
                                                    case 'Medium': echo 'info'; break;
                                                    default: echo 'success';
                                                }
                                            ?>"><?php echo $incident['severity']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($incident['status']) {
                                                    case 'New': echo 'primary'; break;
                                                    case 'In Progress': echo 'info'; break;
                                                    case 'Resolved': echo 'success'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>"><?php echo $incident['status']; ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($incident['reported_by']); ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($incident['created_at'])); ?></td>
                                        <td>
                                            <a href="view_incident_details.php?id=<?php echo $incident['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="bi bi-inbox text-muted d-block mb-3" style="font-size: 2rem;"></i>
                                        <p class="text-muted mb-0">No incidents reported yet</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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