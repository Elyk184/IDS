<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Start with no messages
$_SESSION['success_message'] = null;
$_SESSION['error_message'] = null;

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    // Prevent self-deletion
    if ($user_id === $_SESSION['user_id']) {
        $_SESSION['error_message'] = "You cannot delete your own account.";
        header("Location: manage_users.php");
        exit();
    }
    
    // Get user details for logging
    $stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
    if (!$stmt) {
        $_SESSION['error_message'] = "Database error: " . $conn->error;
        header("Location: manage_users.php");
        exit();
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        $_SESSION['error_message'] = "Error executing user query: " . $stmt->error;
        header("Location: manage_users.php");
        exit();
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Log what we're about to do
            error_log("Starting deletion process for user: " . $user['username']);
            
            // First, check for any dependencies
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM incident_reports WHERE reported_by = ? OR resolved_by = ?");
            if ($check_stmt) {
                $check_stmt->bind_param("ss", $user['username'], $user['username']);
                $check_stmt->execute();
                $count_result = $check_stmt->get_result()->fetch_assoc();
                error_log("Found {$count_result['count']} related incident reports");
                $check_stmt->close();
            }

            // First, update any resolved incidents to remove the resolver
            $update_stmt = $conn->prepare("UPDATE incident_reports SET resolved_by = NULL WHERE resolved_by = ?");
            if (!$update_stmt) {
                throw new Exception("Error preparing resolver update: " . $conn->error);
            }
            $update_stmt->bind_param("s", $user['username']);
            if (!$update_stmt->execute()) {
                throw new Exception("Error updating resolvers: " . $update_stmt->error);
            }
            error_log("Updated resolved incidents");
            $update_stmt->close();
            
            // Delete user's incident reports
            $stmt = $conn->prepare("DELETE FROM incident_reports WHERE reported_by = ?");
            if (!$stmt) {
                throw new Exception("Error preparing incident delete: " . $conn->error);
            }
            $stmt->bind_param("s", $user['username']);
            if (!$stmt->execute()) {
                throw new Exception("Error deleting incidents: " . $stmt->error);
            }
            error_log("Deleted user's incident reports");
            $stmt->close();
            
            // Delete user's OTPs
            $stmt = $conn->prepare("DELETE FROM user_otps WHERE user_id = ?");
            if (!$stmt) {
                throw new Exception("Error preparing OTP delete: " . $conn->error);
            }
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Error deleting OTPs: " . $stmt->error);
            }
            error_log("Deleted user's OTPs");
            $stmt->close();
            
            // Update system settings to remove user reference
            $stmt = $conn->prepare("UPDATE system_settings SET updated_by = NULL WHERE updated_by = ?");
            if (!$stmt) {
                throw new Exception("Error preparing settings update: " . $conn->error);
            }
            $stmt->bind_param("s", $user['username']);
            if (!$stmt->execute()) {
                throw new Exception("Error updating settings: " . $stmt->error);
            }
            error_log("Updated system settings");
            $stmt->close();
            
            // Finally, delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Error preparing user delete: " . $conn->error);
            }
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Error deleting user: " . $stmt->error);
            }
            error_log("Deleted user");
            $stmt->close();
            
            // Log the deletion
            $admin_username = $_SESSION['username'];
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $log_message = "User deleted: {$user['username']} ({$user['role']}) by {$admin_username}";
            
            $stmt = $conn->prepare("INSERT INTO logs (timestamp, source_ip, destination_ip, protocol, alert) 
                                  VALUES (NOW(), ?, 'Server', 'User Management', ?)");
            if (!$stmt) {
                throw new Exception("Error preparing log insert: " . $conn->error);
            }
            $stmt->bind_param("ss", $ip_address, $log_message);
            if (!$stmt->execute()) {
                throw new Exception("Error logging deletion: " . $stmt->error);
            }
            error_log("Logged deletion");
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            error_log("Transaction committed");
            
            $_SESSION['success_message'] = "User {$user['username']} deleted successfully.";
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            error_log("Error in deletion process: " . $e->getMessage());
            $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "User not found.";
        error_log("User not found with ID: " . $user_id);
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
}

header("Location: manage_users.php");
exit();
?>