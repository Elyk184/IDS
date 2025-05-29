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

// Get incident details
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_my_incidents.php");
    exit();
}

$incident_id = (int)$_GET['id'];

// Fetch incident details
$incident_query = "SELECT * FROM incidents WHERE id = ? AND reported_by = ?";
$stmt = $conn->prepare($incident_query);
$stmt->bind_param("is", $incident_id, $username);
$stmt->execute();
$incident = $stmt->get_result()->fetch_assoc();

if (!$incident) {
    header("Location: view_my_incidents.php");
    exit();
}

// Fetch incident notes
$notes_query = "SELECT * FROM incident_notes WHERE incident_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($notes_query);
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$notes = $stmt->get_result();

// Handle new note submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $note_text = trim($_POST['note']);
    if (!empty($note_text)) {
        $insert_note = "INSERT INTO incident_notes (incident_id, note, added_by) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_note);
        $stmt->bind_param("iss", $incident_id, $note_text, $username);
        
        if ($stmt->execute()) {
            header("Location: view_incident.php?id=" . $incident_id . "&note_added=1");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Incident | IDS</title>
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

        /* Incident Details Card */
        .incident-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .incident-card .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1.5rem;
        }

        .incident-card .card-body {
            padding: 2rem;
        }

        /* Badge Styling */
        .badge {
            padding: 0.5em 1em;
            font-weight: 500;
        }

        /* Notes Section */
        .note-item {
            border-left: 4px solid #dee2e6;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
            border-radius: 0 8px 8px 0;
        }

        .note-item:hover {
            border-left-color: #0d6efd;
            background-color: #f0f4f8;
        }

        .note-meta {
            font-size: 0.875rem;
            color: #6c757d;
        }

        /* Form Styling */
        .form-control:focus {
            box-shadow: none;
            border-color: #0d6efd;
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
                    <h4 class="mb-1">Incident Details</h4>
                    <p class="text-muted mb-0">Viewing incident #<?php echo $incident['id']; ?></p>
                </div>
                <a href="view_my_incidents.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-2"></i>Back to My Incidents
                </a>
            </div>

            <!-- Success Message -->
            <?php if (isset($_GET['note_added'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>Note added successfully
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Incident Details -->
            <div class="incident-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($incident['title']); ?></h5>
                        <div>
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
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Location:</strong></p>
                            <p class="text-muted"><?php echo htmlspecialchars($incident['location']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Reported On:</strong></p>
                            <p class="text-muted"><?php echo date('F j, Y g:i A', strtotime($incident['created_at'])); ?></p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <p class="mb-1"><strong>Description:</strong></p>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($incident['description'])); ?></p>
                    </div>

                    <!-- Notes Section -->
                    <div class="mt-4">
                        <h5 class="mb-3">Notes</h5>
                        <?php if ($notes->num_rows > 0): ?>
                            <?php while ($note = $notes->fetch_assoc()): ?>
                                <div class="note-item">
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($note['note'])); ?></p>
                                    <div class="note-meta">
                                        <span class="me-3">
                                            <i class="bi bi-person me-1"></i>
                                            <?php echo htmlspecialchars($note['added_by']); ?>
                                        </span>
                                        <span>
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo date('M j, Y g:i A', strtotime($note['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">No notes added yet.</p>
                        <?php endif; ?>

                        <!-- Add Note Form -->
                        <form action="" method="POST" class="mt-4">
                            <div class="mb-3">
                                <label for="note" class="form-label">Add a Note</label>
                                <textarea class="form-control" id="note" name="note" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="add_note" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Add Note
                            </button>
                        </form>
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