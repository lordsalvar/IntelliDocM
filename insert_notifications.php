<?php
require_once 'db_connect.php'; // Include database connection

function insertNotification($user_id, $message)
{
    global $conn;

    $sql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $message);

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

// Example Usage: When a proposal is approved
$user_id = 1; // Replace with the user's ID
$message = "Your activity proposal has been approved!";
insertNotification($user_id, $message);
