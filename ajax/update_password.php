<?php
session_start();
require_once '../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$currentPassword = $_POST['currentPassword'] ?? '';
$newPassword = $_POST['newPassword'] ?? '';
$user_id = $_SESSION['user_id'];

// Verify current password
$verify_query = "SELECT password FROM users WHERE id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Verify current password using password_verify
if (!$user || !password_verify($currentPassword, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

// Hash the new password using PASSWORD_DEFAULT
$hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);

// Update password with properly hashed password
$update_query = "UPDATE users SET password = ? WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $hashed_password, $user_id);

if ($stmt->execute()) {
    // Log the password change
    $log_query = "INSERT INTO activity_log (user_id, activity_type, description) VALUES (?, 'password_change', 'User changed their password')";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("i", $user_id);
    $log_stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating password']);
}
