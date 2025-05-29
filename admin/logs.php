<?php
session_start();
require __DIR__ . '/../includes/db.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialize filters
$where_conditions = [];
$params = [];
$types = "";

// Date filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$where_conditions[] = "DATE(timestamp) BETWEEN ? AND ?";
$params[] = $start_date;
$params[] = $end_date;
$types .= "ss";

// Alert type filter
if (isset($_GET['alert_type']) && !empty($_GET['alert_type'])) {
    $where_conditions[] = "alert LIKE ?";
    $params[] = "%" . $_GET['alert_type'] . "%";
    $types .= "s";
}

// Build the query
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
$query = "SELECT * FROM logs {$where_clause} ORDER BY timestamp DESC LIMIT 1000";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();

// Get unique alert types for filter
$alert_types = $conn->query("SELECT DISTINCT SUBSTRING_INDEX(alert, ':', 1) as alert_type FROM logs");
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Logs - Admin Dashboard</title>
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
        .log-entry {
            transition: background-color 0.2s;
        }
        .log-entry:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        .alert-Failed { color: #dc3545; }
        .alert-Success { color: #198754; }
        .alert-Warning { color: #ffc107; }
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
                            <a class="nav-link" href="manage_users.php">
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
                            <a class="nav-link active" href="logs.php">
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
                    <h2><i class="bi bi-journal-text me-2"></i>System Logs</h2>
                    <div>
                        <span class="badge bg-primary">
                            <i class="bi bi-clock-history me-1"></i>
                            <?php echo date('Y-m-d H:i:s'); ?>
                        </span>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Alert Type</label>
                                <select class="form-select" name="alert_type">
                                    <option value="">All Types</option>
                                    <?php while ($type = $alert_types->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($type['alert_type']); ?>"
                                                <?php echo (isset($_GET['alert_type']) && $_GET['alert_type'] == $type['alert_type']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['alert_type']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-filter me-2"></i>Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Source IP</th>
                                        <th>Destination IP</th>
                                        <th>Protocol</th>
                                        <th>Alert</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($logs && $logs->num_rows > 0): ?>
                                        <?php while ($log = $logs->fetch_assoc()): ?>
                                            <?php
                                            $alert_class = '';
                                            if (stripos($log['alert'], 'failed') !== false) {
                                                $alert_class = 'alert-Failed';
                                            } elseif (stripos($log['alert'], 'success') !== false) {
                                                $alert_class = 'alert-Success';
                                            } elseif (stripos($log['alert'], 'warning') !== false) {
                                                $alert_class = 'alert-Warning';
                                            }
                                            ?>
                                            <tr class="log-entry">
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['timestamp'])); ?></td>
                                                <td><?php echo htmlspecialchars($log['source_ip']); ?></td>
                                                <td><?php echo htmlspecialchars($log['destination_ip']); ?></td>
                                                <td><?php echo htmlspecialchars($log['protocol']); ?></td>
                                                <td class="<?php echo $alert_class; ?>">
                                                    <?php echo htmlspecialchars($log['alert']); ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No logs found for the selected criteria.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 