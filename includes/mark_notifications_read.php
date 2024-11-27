<?php
session_start();
require_once 'database.php';
require_once 'includes/notifications.php';


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
markAllNotificationsAsRead($user_id, $conn);

echo json_encode(['success' => true, 'message' => 'Notifications marked as read.']);
