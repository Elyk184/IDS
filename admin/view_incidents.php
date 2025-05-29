<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get filter parameters
$severity_filter = isset($_GET['severity']) ? $_GET['severity'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT * FROM incidents WHERE 1=1";
if ($severity_filter) {
    $query .= " AND severity = '" . $conn->real_escape_string($severity_filter) . "'";
}
if ($status_filter) {
    $query .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($search) {
    $query .= " AND (title LIKE '%" . $conn->real_escape_string($search) . "%' 
                OR description LIKE '%" . $conn->real_escape_string($search) . "%'
                OR reported_by LIKE '%" . $conn->real_escape_string($search) . "%')";
}
$query .= " ORDER BY created_at DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Incidents - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="float-start">Incident Reports</h2>
                        <a href="admin_dashboard.php" class="btn btn-secondary float-end">Back to Dashboard</a>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="row mb-4">
                            <div class="col-md-3">
                                <select name="severity" class="form-select">
                                    <option value="">All Severities</option>
                                    <option value="Critical" <?php echo $severity_filter === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                                    <option value="High" <?php echo $severity_filter === 'High' ? 'selected' : ''; ?>>High</option>
                                    <option value="Medium" <?php echo $severity_filter === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="Low" <?php echo $severity_filter === 'Low' ? 'selected' : ''; ?>>Low</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="New" <?php echo $status_filter === 'New' ? 'selected' : ''; ?>>New</option>
                                    <option value="In Progress" <?php echo $status_filter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Resolved" <?php echo $status_filter === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="Closed" <?php echo $status_filter === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search incidents..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>

                        <!-- Incidents Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Reported By</th>
                                        <th>Severity</th>
                                        <th>Status</th>
                                        <th>Date Reported</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td><?php echo htmlspecialchars($row['reported_by']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch($row['severity']) {
                                                            case 'Critical': echo 'danger'; break;
                                                            case 'High': echo 'warning'; break;
                                                            case 'Medium': echo 'info'; break;
                                                            default: echo 'success';
                                                        }
                                                    ?>">
                                                        <?php echo htmlspecialchars($row['severity']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                                                <td>
                                                    <a href="view_incident_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatus<?php echo $row['id']; ?>">
                                                        <i class="bi bi-pencil"></i> Update
                                                    </button>
                                                </td>
                                            </tr>
                                            <!-- Status Update Modal -->
                                            <div class="modal fade" id="updateStatus<?php echo $row['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update Incident Status</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form action="update_incident_status.php" method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="incident_id" value="<?php echo $row['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="status<?php echo $row['id']; ?>" class="form-label">Status</label>
                                                                    <select class="form-select" id="status<?php echo $row['id']; ?>" name="status" required>
                                                                        <option value="New" <?php echo $row['status'] === 'New' ? 'selected' : ''; ?>>New</option>
                                                                        <option value="In Progress" <?php echo $row['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                                        <option value="Resolved" <?php echo $row['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                                        <option value="Closed" <?php echo $row['status'] === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="notes<?php echo $row['id']; ?>" class="form-label">Notes</label>
                                                                    <textarea class="form-control" id="notes<?php echo $row['id']; ?>" name="notes" rows="3"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Update Status</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No incidents found</td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 