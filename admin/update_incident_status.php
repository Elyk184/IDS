<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $incident_id = isset($_POST['incident_id']) ? (int)$_POST['incident_id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    if ($incident_id && $status) {
        // Update incident status
        $stmt = $conn->prepare("UPDATE incidents SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $incident_id);
        
        if ($stmt->execute()) {
            // If there are notes, add them to incident_notes table
            if (!empty($notes)) {
                $stmt = $conn->prepare("INSERT INTO incident_notes (incident_id, note, added_by, created_at) VALUES (?, ?, ?, NOW())");
                $added_by = $_SESSION['username'];
                $stmt->bind_param("iss", $incident_id, $notes, $added_by);
                $stmt->execute();
            }
            
            $_SESSION['success_message'] = "Incident status updated successfully.";
        } else {
            $_SESSION['error_message'] = "Error updating incident status.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid input data.";
    }
}

header("Location: view_incidents.php");
exit(); 