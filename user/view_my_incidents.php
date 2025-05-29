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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total incidents count
$count_query = "SELECT COUNT(*) as total FROM incidents WHERE reported_by = ?";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$total_incidents = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_incidents / $limit);

// Get incidents with pagination
$incidents_query = "SELECT i.*, 
    (SELECT COUNT(*) FROM incident_notes WHERE incident_id = i.id) as note_count 
    FROM incidents i 
    WHERE i.reported_by = ? 
    ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($incidents_query);
$stmt->bind_param("sii", $username, $limit, $offset);
$stmt->execute();
$incidents = $stmt->get_result();

// Handle success message
$success_message = '';
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Incidents | IDS</title>
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

        /* Incidents Table Card */
        .incidents-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .incidents-card .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1.5rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            background-color: rgba(0,0,0,0.02);
            font-weight: 600;
            color: #495057;
        }

        .table td {
            vertical-align: middle;
        }

        /* Badge Styling */
        .badge {
            padding: 0.5em 1em;
            font-weight: 500;
        }

        /* Pagination */
        .pagination {
            margin-bottom: 0;
        }

        .page-link {
            padding: 0.5rem 1rem;
            color: #2c3034;
            border: none;
            margin: 0 2px;
        }

        .page-link:hover {
            background-color: #e9ecef;
            color: #000;
        }

        .page-item.active .page-link {
            background-color: #2c3034;
            border-color: #2c3034;
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

            .table-responsive {
                border-radius: 12px;
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
                    <a href="view_my_incidents.php" class="active">
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
                    <h4 class="mb-1">My Incidents</h4>
                    <p class="text-muted mb-0">View and manage your reported incidents</p>
                </div>
                <a href="report_incident.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Report New Incident
                </a>
            </div>

            <!-- Success Message -->
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Incidents Table -->
            <div class="incidents-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Incidents</h5>
                    <span class="badge bg-secondary"><?php echo $total_incidents; ?> Total</span>
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
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($incidents->num_rows > 0): ?>
                                <?php while ($incident = $incidents->fetch_assoc()): ?>
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
                                            ?>"><?php echo htmlspecialchars($incident['severity']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($incident['status']) {
                                                    case 'New': echo 'primary'; break;
                                                    case 'In Progress': echo 'info'; break;
                                                    case 'Resolved': echo 'success'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>"><?php echo htmlspecialchars($incident['status']); ?></span>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($incident['created_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#incidentModal<?php echo $incident['id']; ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>

                                            <!-- Incident Details Modal -->
                                            <div class="modal fade" id="incidentModal<?php echo $incident['id']; ?>" tabindex="-1" 
                                                 aria-labelledby="incidentModalLabel<?php echo $incident['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="incidentModalLabel<?php echo $incident['id']; ?>">
                                                                Incident #<?php echo $incident['id']; ?>
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row mb-4">
                                                                <div class="col-md-8">
                                                                    <h5 class="mb-3"><?php echo htmlspecialchars($incident['title']); ?></h5>
                                                                </div>
                                                                <div class="col-md-4 text-md-end">
                                                                    <span class="badge bg-<?php 
                                                                        switch($incident['severity']) {
                                                                            case 'Critical': echo 'danger'; break;
                                                                            case 'High': echo 'warning'; break;
                                                                            case 'Medium': echo 'info'; break;
                                                                            default: echo 'success';
                                                                        }
                                                                    ?> me-2"><?php echo $incident['severity']; ?></span>
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

                                                            <div class="row mb-4">
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><strong>Location:</strong></p>
                                                                    <p class="text-muted"><?php echo htmlspecialchars($incident['location']); ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><strong>Reported On:</strong></p>
                                                                    <p class="text-muted">
                                                                        <?php echo date('F j, Y g:i A', strtotime($incident['created_at'])); ?>
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <div class="mb-4">
                                                                <p class="mb-1"><strong>Description:</strong></p>
                                                                <p class="text-muted">
                                                                    <?php echo nl2br(htmlspecialchars($incident['description'])); ?>
                                                                </p>
                                                            </div>

                                                            <div class="d-flex align-items-center">
                                                                <span class="text-muted">
                                                                    <i class="bi bi-chat-left-text me-1"></i>
                                                                    <?php echo $incident['note_count']; ?> Notes
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <a href="view_incident.php?id=<?php echo $incident['id']; ?>" 
                                                               class="btn btn-primary">
                                                                <i class="bi bi-arrow-right me-2"></i>View Full Details
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="bi bi-inbox text-muted d-block mb-3" style="font-size: 2rem;"></i>
                                        <p class="text-muted mb-0">No incidents reported yet</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white border-top">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>" aria-label="Previous">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>" aria-label="Next">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
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