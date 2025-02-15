<?php
session_start();
require_once '../../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$booking_id = $_POST['booking_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$booking_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$sql = "UPDATE bookings SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $status, $booking_id);

$response = ['success' => $stmt->execute()];
if (!$response['success']) {
    $response['message'] = 'Failed to update booking status';
}

echo json_encode($response);
