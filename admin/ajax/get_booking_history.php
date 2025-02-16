<?php
session_start();
require_once '../../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

$sql = "SELECT 
        b.id, b.booking_date, b.start_time, b.end_time, b.status,
        f.name AS facility_name,
        c.club_name AS club_name,
        GROUP_CONCAT(r.room_number) AS room_numbers
    FROM bookings b
    LEFT JOIN facilities f ON b.facility_id = f.id
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN club_memberships cm ON u.id = cm.user_id
    LEFT JOIN clubs c ON cm.club_id = c.club_id
    LEFT JOIN booking_rooms br ON b.id = br.booking_id
    LEFT JOIN rooms r ON br.room_id = r.id
    GROUP BY b.id
    ORDER BY b.booking_date DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $row['booking_date'] = date('M d, Y', strtotime($row['booking_date']));
    $row['start_time'] = date('h:i A', strtotime($row['start_time']));
    $row['end_time'] = date('h:i A', strtotime($row['end_time']));
    $row['club_name'] = $row['club_name'] ?? 'Individual Booking';
    $bookings[] = $row;
}

echo json_encode(['success' => true, 'data' => $bookings]);
