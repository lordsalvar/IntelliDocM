<?php
session_start();
require_once '../../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$booking_id = $_GET['booking_id'] ?? null;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Missing booking ID']);
    exit;
}

$sql = "SELECT b.*, f.name as facility_name, u.full_name as user_name,
        GROUP_CONCAT(r.room_number) as room_numbers
        FROM bookings b
        JOIN facilities f ON b.facility_id = f.id
        JOIN users u ON b.user_id = u.id
        LEFT JOIN booking_rooms br ON b.id = br.booking_id
        LEFT JOIN rooms r ON br.room_id = r.id
        WHERE b.id = ?
        GROUP BY b.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if ($booking) {
    $booking['booking_date'] = date('M d, Y', strtotime($booking['booking_date']));
    $booking['start_time'] = date('h:i A', strtotime($booking['start_time']));
    $booking['end_time'] = date('h:i A', strtotime($booking['end_time']));
    $booking['created_at'] = date('M d, Y h:i A', strtotime($booking['created_at']));
    echo json_encode(['success' => true, 'data' => $booking]);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
}
