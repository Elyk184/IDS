<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';
$result = $conn->query("SELECT * FROM logs ORDER BY timestamp DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Intrusion Detection System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Welcome, <?php echo $_SESSION['username']; ?></h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
    
    <h2 class="mb-3">Logs</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Timestamp</th>
                    <th>Source IP</th>
                    <th>Destination IP</th>
                    <th>Protocol</th>
                    <th>Alert</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['timestamp']; ?></td>
                        <td><?php echo $row['source_ip']; ?></td>
                        <td><?php echo $row['destination_ip']; ?></td>
                        <td><?php echo $row['protocol']; ?></td>
                        <td><?php echo $row['alert']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
   
</body>
</html>