<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get incident ID
$incident_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$incident_id) {
    header("Location: view_incidents.php");
    exit();
}

// Get incident details
$stmt = $conn->prepare("SELECT * FROM incidents WHERE id = ?");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$incident = $stmt->get_result()->fetch_assoc();

if (!$incident) {
    header("Location: view_incidents.php");
    exit();
}

// Get incident notes
$stmt = $conn->prepare("SELECT * FROM incident_notes WHERE incident_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$notes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Details - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="float-start">Incident Details</h2>
                        <a href="view_incidents.php" class="btn btn-secondary float-end">Back to Incidents</a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Basic Information</h4>
                                <table class="table">
                                    <tr>
                                        <th>ID:</th>
                                        <td><?php echo htmlspecialchars($incident['id']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Title:</th>
                                        <td><?php echo htmlspecialchars($incident['title']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Reported By:</th>
                                        <td><?php echo htmlspecialchars($incident['reported_by']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date Reported:</th>
                                        <td><?php echo date('Y-m-d H:i', strtotime($incident['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($incident['status']) {
                                                    case 'New': echo 'primary'; break;
                                                    case 'In Progress': echo 'warning'; break;
                                                    case 'Resolved': echo 'success'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars($incident['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Severity:</th>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($incident['severity']) {
                                                    case 'Critical': echo 'danger'; break;
                                                    case 'High': echo 'warning'; break;
                                                    case 'Medium': echo 'info'; break;
                                                    default: echo 'success';
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars($incident['severity']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Location:</th>
                                        <td><?php echo htmlspecialchars($incident['location']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h4>Description</h4>
                                <div class="card">
                                    <div class="card-body">
                                        <?php echo nl2br(htmlspecialchars($incident['description'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <h4>Notes & Updates</h4>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNote">
                                        <i class="bi bi-plus-circle"></i> Add Note
                                    </button>
                                </div>
                                <?php if ($notes && $notes->num_rows > 0): ?>
                                    <div class="timeline">
                                        <?php while ($note = $notes->fetch_assoc()): ?>
                                            <div class="card mb-3">
                                                <div class="card-header">
                                                    <strong><?php echo htmlspecialchars($note['added_by']); ?></strong>
                                                    <span class="text-muted float-end">
                                                        <?php echo date('Y-m-d H:i', strtotime($note['created_at'])); ?>
                                                    </span>
                                                </div>
                                                <div class="card-body">
                                                    <?php echo nl2br(htmlspecialchars($note['note'])); ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No notes added yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div class="modal fade" id="addNote" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="update_incident_status.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="incident_id" value="<?php echo $incident['id']; ?>">
                        <div class="mb-3">
                            <label for="status" class="form-label">Update Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="<?php echo htmlspecialchars($incident['status']); ?>">Keep Current (<?php echo htmlspecialchars($incident['status']); ?>)</option>
                                <option value="New">New</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 