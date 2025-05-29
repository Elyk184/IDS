<?php
require __DIR__ . '/../includes/db.php';

$conn->select_db('ids');
$result = $conn->query('SHOW CREATE TABLE incident_reports');

if ($result) {
    $row = $result->fetch_assoc();
    print_r($row);
} else {
    echo "Error: " . $conn->error;
}
?> 