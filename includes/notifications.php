<?php
// Function to fetch unread notifications
function getUnreadNotifications($user_id, $conn)
{
    $sql = "SELECT * FROM notifications WHERE user_id = ? AND status = 'unread' ORDER BY timestamp DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Database query failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $notifications;
}

// Function to mark all notifications as read
function markAllNotificationsAsRead($user_id, $conn)
{
    $sql = "UPDATE notifications SET status = 'read' WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Database query failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}
