<?php
session_start();
require_once 'database.php';

// Debugging: Print session user_id
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["error" => "User not logged in.", "session" => $_SESSION]));
}

$user_id = $_SESSION['user_id']; // Use user_id from session

// Debugging: Log user_id to a file
file_put_contents('debug_log.txt', "Fetching notifications for user_id: $user_id\n", FILE_APPEND);

// Fetch notifications
$sql = "SELECT id, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Debugging: Log results
file_put_contents('debug_log.txt', "Notifications fetched: " . json_encode($notifications) . "\n", FILE_APPEND);

echo json_encode($notifications);

$stmt->close();
$conn->close();
