<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: ../notifications.php');
    exit();
}

$notification_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$update_query = "UPDATE notifications SET status = 'read' 
                WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ii", $notification_id, $user_id);
$stmt->execute();

header('Location: ../notifications.php');
