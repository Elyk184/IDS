<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

// Get user's incidents
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user profile data
$profile_query = "SELECT email, phone FROM users WHERE id = ?";
$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_profile = $stmt->get_result()->fetch_assoc();

$incidents_query = "SELECT 
    COUNT(*) as total_incidents,
    SUM(CASE WHEN status = 'New' THEN 1 ELSE 0 END) as new_incidents,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved
FROM incidents 
WHERE reported_by = ?";

$stmt = $conn->prepare($incidents_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$incidents_stats = $stmt->get_result()->fetch_assoc();

// Get user's recent incidents
$recent_incidents_query = "SELECT * FROM incidents 
    WHERE reported_by = ? 
    ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($recent_incidents_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$recent_incidents = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | IDS</title>
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
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        /* Recent Incidents Card */
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

        .incident-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: background-color 0.2s ease;
        }

        .incident-item:hover {
            background-color: rgba(0,0,0,0.02);
        }

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
                <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($user_profile['email']))); ?>?s=200&d=mp" 
                     alt="Profile" class="profile-image">
                <h5 class="mb-1 text-white"><?php echo htmlspecialchars($username); ?></h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($user_profile['email']); ?></p>
            </div>

            <!-- Navigation -->
            <ul class="nav-menu">
                <li>
                    <a href="user_dashboard.php" class="active">
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
                    <h4 class="mb-1">Welcome back, <?php echo htmlspecialchars($username); ?>!</h4>
                    <p class="text-muted mb-0">Here's what's happening with your incidents</p>
                </div>
                <a href="report_incident.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Report New Incident
                </a>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number text-primary"><?php echo $incidents_stats['total_incidents']; ?></div>
                        <div class="stat-label">Total Incidents</div>
                        <small class="text-muted">All time</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number text-warning"><?php echo $incidents_stats['new_incidents']; ?></div>
                        <div class="stat-label">New Incidents</div>
                        <small class="text-muted">Awaiting response</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number text-info"><?php echo $incidents_stats['in_progress']; ?></div>
                        <div class="stat-label">In Progress</div>
                        <small class="text-muted">Being handled</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number text-success"><?php echo $incidents_stats['resolved']; ?></div>
                        <div class="stat-label">Resolved</div>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>

            <!-- Recent Incidents -->
            <div class="incidents-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Incidents</h5>
                    <a href="view_my_incidents.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recent_incidents && $recent_incidents->num_rows > 0): ?>
                        <?php while ($incident = $recent_incidents->fetch_assoc()): ?>
                            <div class="incident-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <a href="view_incident.php?id=<?php echo $incident['id']; ?>" 
                                               class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($incident['title']); ?>
                                            </a>
                                        </h6>
                                        <p class="mb-1 text-muted">
                                            <?php echo htmlspecialchars(substr($incident['description'], 0, 100)) . '...'; ?>
                                        </p>
                                        <div class="mt-2">
                                            <span class="badge bg-<?php 
                                                switch($incident['severity']) {
                                                    case 'Critical': echo 'danger'; break;
                                                    case 'High': echo 'warning'; break;
                                                    case 'Medium': echo 'info'; break;
                                                    default: echo 'success';
                                                }
                                            ?>"><?php echo $incident['severity']; ?></span>
                                            <span class="badge bg-<?php 
                                                switch($incident['status']) {
                                                    case 'New': echo 'primary'; break;
                                                    case 'In Progress': echo 'info'; break;
                                                    case 'Resolved': echo 'success'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>"><?php echo $incident['status']; ?></span>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('M j, H:i', strtotime($incident['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-3 mb-3">You haven't reported any incidents yet.</p>
                            <a href="report_incident.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Report Your First Incident
                            </a>
                        </div>
                    <?php endif; ?>
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